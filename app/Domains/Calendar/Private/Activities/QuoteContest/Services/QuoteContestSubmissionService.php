<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\MyEntryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\PickerQuoteViewModel;
use App\Domains\Quote\Public\Api\Contracts\QuoteDto;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Story\Public\Api\StoryPublicApi;
use App\Domains\Story\Public\Contracts\StorySummaryDto;
use App\Domains\Story\Public\Contracts\StoryVisibility;

/**
 * The reader's side of a quote contest: what they may submit, and what they
 * currently have in the running.
 *
 * Eligibility is computed here and nowhere else (architecture §3.2). Greying a
 * row in the picker is a courtesy; the service is the enforcement point.
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
