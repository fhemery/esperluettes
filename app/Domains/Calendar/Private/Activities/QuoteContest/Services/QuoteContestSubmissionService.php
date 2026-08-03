<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SubmissionRefusedException;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\MyEntryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\PickerQuoteViewModel;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Quote\Public\Api\Contracts\QuoteDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StorySummaryDto;
use App\Domains\Story\Public\Contracts\StoryVisibility;
use Illuminate\Support\Facades\DB;

/**
 * The reader's side of a quote contest: what they may submit, and what they
 * currently have in the running.
 *
 * Eligibility is computed here and nowhere else (architecture §3.2). Greying a
 * row in the picker is a courtesy; the service is the enforcement point — every
 * rule the picker expresses is re-checked here against a request that may have
 * been forged.
 */
class QuoteContestSubmissionService
{
    /** The quote's story is neither `public` nor `community` (assumption A2). */
    public const REASON_PRIVATE_STORY = 'private_story';

    /** The quote's story carries `is_excluded_from_events`. */
    public const REASON_EXCLUDED_FROM_EVENTS = 'excluded_from_events';

    public function __construct(
        private readonly QuotePublicApi $quotes,
        private readonly StoryPublicApi $stories,
        private readonly QuoteContestConfigService $config,
        private readonly QuoteContestPhaseService $phases,
    ) {}

    /**
     * The reader's whole quote book, newest first, each row carrying the reason
     * it cannot be entered — or null when it can.
     *
     * Costs one call to the Quote API plus **one batched story read** over the
     * distinct story ids: the query count follows the number of stories quoted
     * from, never the number of quotes.
     *
     * @return array<int, PickerQuoteViewModel>
     */
    public function pickerFor(int $userId): array
    {
        $quotes = $this->quotes->getAllForOwner($userId)->items;

        if ($quotes === []) {
            return [];
        }

        $storyIds = array_values(array_unique(array_map(
            static fn (QuoteDto $quote) => $quote->storyId,
            $quotes,
        )));

        $stories = $this->stories->getStoriesByIds($storyIds);

        return array_map(
            fn (QuoteDto $quote) => new PickerQuoteViewModel(
                id: $quote->id,
                highlightedText: $quote->highlightedText,
                storyTitle: $quote->storyTitle,
                storyUrl: $quote->storyUrl,
                chapterTitle: $quote->chapterTitle,
                chapterUrl: $quote->chapterUrl,
                ineligibilityReason: $this->ineligibilityReason($stories[$quote->storyId] ?? null),
            ),
            $quotes,
        );
    }

    /**
     * The reader's live entry in each category of this contest, keyed by
     * category id. Withdrawn entries are excluded: the slot they used to hold
     * is free again (decision #18).
     *
     * @return array<int, MyEntryViewModel>
     */
    public function currentEntriesFor(int $activityId, int $userId): array
    {
        $entries = QuoteContestEntry::query()
            ->where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->whereNull('withdrawn_at')
            ->orderBy('id')
            ->get();

        $byCategory = [];
        foreach ($entries as $entry) {
            $byCategory[(int) $entry->category_id] = new MyEntryViewModel(
                id: (int) $entry->id,
                highlightedText: (string) $entry->highlighted_text,
                storyTitle: (string) $entry->story_title,
                storyUrl: route('stories.show', ['slug' => $entry->story_slug]),
                chapterTitle: (string) $entry->chapter_title,
                chapterUrl: route('chapters.show', [
                    'storySlug' => $entry->story_slug,
                    'chapterSlug' => $entry->chapter_slug,
                ]),
            );
        }

        return $byCategory;
    }

    /**
     * Enter a quote in a category, replacing whatever the reader already has
     * there.
     *
     * Every guard of architecture §3.3 is applied here, in order: the contest
     * must be taking submissions, the category must belong to it, the quote
     * must come back from `getOwnedQuote()` — the contest never queries
     * `quotes` itself — and it must still be eligible.
     *
     * The row written is a full snapshot (§2.1): it holds no live reference it
     * ever reads back, which is what makes it survive the source quote's
     * edition or deletion. The reader's private note is never read and never
     * stored (assumption A1).
     *
     * @throws SubmissionRefusedException on any forged or out-of-phase request
     */
    public function submit(int $activityId, int $categoryId, int $quoteId, int $userId): QuoteContestEntry
    {
        $this->assertSubmissionsOpen($activityId);

        $category = QuoteContestCategory::query()
            ->where('activity_id', $activityId)
            ->find($categoryId);

        if ($category === null) {
            throw SubmissionRefusedException::unknownCategory();
        }

        $quote = $this->quotes->getOwnedQuote($quoteId, $userId);

        if ($quote === null) {
            throw SubmissionRefusedException::quoteNotOwned();
        }

        $snapshot = $this->snapshot($quote);

        return DB::transaction(function () use ($activityId, $categoryId, $userId, $snapshot) {
            // One non-withdrawn entry per (category, user), enforced here and
            // not by a unique index: MySQL counts every NULL `withdrawn_at` as
            // distinct, so an index would allow the duplicates it should stop.
            // A withdrawn row is evidence and stays (§2.3); the sitting one is
            // hard-deleted, and its votes cascade away by FK.
            QuoteContestEntry::query()
                ->where('category_id', $categoryId)
                ->where('user_id', $userId)
                ->whereNull('withdrawn_at')
                ->delete();

            return QuoteContestEntry::create(array_merge($snapshot, [
                'activity_id' => $activityId,
                'category_id' => $categoryId,
                'user_id' => $userId,
            ]));
        });
    }

    /**
     * Withdraw one's own entry, freely, until submissions close (decision #12).
     *
     * A hard delete: no vote can exist yet, so nothing is lost. An entry
     * already withdrawn for privacy is not the reader's to delete — it is the
     * evidence that forbids deleting its category (§2.3).
     *
     * @throws SubmissionRefusedException when the entry is not the caller's, or
     *                                    submissions are closed
     */
    public function withdraw(int $entryId, int $userId): void
    {
        $entry = QuoteContestEntry::query()
            ->where('user_id', $userId)
            ->whereNull('withdrawn_at')
            ->find($entryId);

        if ($entry === null) {
            throw SubmissionRefusedException::entryNotOwned();
        }

        $this->assertSubmissionsOpen((int) $entry->activity_id);

        $entry->delete();
    }

    /**
     * Withdraw every live entry drawn from a story that lost its eligibility —
     * turned private, or excluded from events (§2.3).
     *
     * A soft flag, not a delete (decision #18): the votes rows stay, and every
     * count and every listing filters on `withdrawn_at IS NULL`, so they stop
     * counting. An accidental visibility toggle is therefore recoverable by
     * hand, and re-entering stays the reader's own action — nothing here
     * restores an entry when the story comes back.
     *
     * One indexed `UPDATE`, whatever the entry count, and a no-op for the
     * overwhelming majority of stories, which have no entry at all. Entries
     * already withdrawn keep their original stamp.
     */
    public function withdrawEntriesForStory(int $storyId): void
    {
        QuoteContestEntry::query()
            ->where('story_id', $storyId)
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);
    }

    /**
     * @throws SubmissionRefusedException unless the contest is taking submissions
     */
    private function assertSubmissionsOpen(int $activityId): void
    {
        $activity = Activity::query()->find($activityId);
        $settings = $this->config->settingsFor($activityId);

        if ($activity === null || $settings === null) {
            throw SubmissionRefusedException::outsideSubmissionPhase();
        }

        if ($this->phases->phaseFor($activity, $settings) !== QuoteContestPhase::Submissions) {
            throw SubmissionRefusedException::outsideSubmissionPhase();
        }
    }

    /**
     * The snapshot columns of an entry, built from one quote.
     *
     * `QuoteDto` carries the story and chapter *URLs* but not the raw slugs the
     * table stores, so the slugs are resolved here from the Story API — whose
     * DTOs carry them — rather than by widening a DTO three domains read. The
     * two batched reads are the same ones eligibility needs anyway.
     *
     * @return array<string, mixed>
     * @throws SubmissionRefusedException when the quote is not eligible
     */
    private function snapshot(QuoteDto $quote): array
    {
        $story = $this->stories->getStoriesByIds([$quote->storyId])[$quote->storyId] ?? null;

        $reason = $this->ineligibilityReason($story);
        if ($reason !== null) {
            throw SubmissionRefusedException::ineligibleQuote($reason);
        }

        $chapter = $this->stories->getChaptersByIds([$quote->chapterId])[$quote->chapterId] ?? null;
        if ($chapter === null) {
            // Nothing to snapshot: `chapter_title` and `chapter_slug` are NOT
            // NULL, and a link to a chapter that no longer exists is not an
            // entry worth creating.
            throw SubmissionRefusedException::unresolvableChapter();
        }

        $authorIds = $this->stories->getAuthorIdsByStoryIds([$quote->storyId])[$quote->storyId] ?? [];

        return [
            // Provenance only: no read path ever dereferences these two.
            'quote_id' => $quote->id,
            'story_id' => $quote->storyId,
            'highlighted_text' => $quote->highlightedText,
            'story_title' => $story->title,
            'story_slug' => $story->slug,
            'chapter_id' => $chapter->id,
            'chapter_title' => $chapter->title,
            'chapter_slug' => $chapter->slug,
            // Who, not their names: those are resolved live (decision #19).
            'author_user_ids' => array_values(array_map('intval', $authorIds)),
        ];
    }

    /**
     * A story that no longer resolves is treated as private: nobody can read it
     * any more, which is exactly what the `private_story` reason tells the
     * reader. The reason set stays the two of §3.2.
     */
    private function ineligibilityReason(?StorySummaryDto $story): ?string
    {
        if ($story === null) {
            return self::REASON_PRIVATE_STORY;
        }

        if (! in_array($story->visibility, [StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY], true)) {
            return self::REASON_PRIVATE_STORY;
        }

        if ($story->is_excluded_from_events) {
            return self::REASON_EXCLUDED_FROM_EVENTS;
        }

        return null;
    }
}
