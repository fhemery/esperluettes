<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SeededShuffle;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\VoteRefusedException;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VoteCategoryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VoteEntryViewModel;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Shared\Contracts\ProfilePublicApi;

/**
 * The reader's ballot: what they may vote for, and their one choice per
 * category.
 *
 * Two rules shape every query here. Withdrawn entries are invisible — a
 * privacy-withdrawn row still exists, but it is out of every listing and every
 * count (§2.3). And **no submitter identity and no vote count ever leaves this
 * service** towards a reader: the view models it builds have nowhere to put
 * them (§3.3), which is what makes the anonymity of decision #2 a query-shape
 * guarantee rather than a template convention.
 */
class QuoteContestVoteService
{
    public function __construct(
        private readonly QuoteContestConfigService $config,
        private readonly QuoteContestPhaseService $phases,
        private readonly ProfilePublicApi $profiles,
    ) {}

    /**
     * The whole ballot of one contest for one reader: every category in display
     * order, each holding its live entries in that reader's own shuffle.
     *
     * Four queries whatever the size of the contest — the categories, their
     * entries, the reader's own votes, and one batched profile read for every
     * author named across the board (decision #19, #20: no cache, because there
     * is nothing left to save).
     *
     * @return array<int, VoteCategoryViewModel>
     */
    public function ballotFor(int $activityId, int $userId): array
    {
        $categories = $this->config->categoriesFor($activityId);

        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = $categories->pluck('id')->map(intval(...))->all();

        $entries = QuoteContestEntry::query()
            ->whereIn('category_id', $categoryIds)
            ->whereNull('withdrawn_at')
            ->orderBy('id')
            ->get();

        $myVotes = QuoteContestVote::query()
            ->where('user_id', $userId)
            ->whereIn('category_id', $categoryIds)
            ->pluck('entry_id', 'category_id');

        $names = $this->authorNames($entries);

        $byCategory = [];
        foreach ($entries as $entry) {
            $byCategory[(int) $entry->category_id][] = $this->toViewModel($entry, $names);
        }

        return $categories
            ->map(function (QuoteContestCategory $category) use ($byCategory, $myVotes, $userId) {
                $categoryId = (int) $category->id;
                $vote = $myVotes[$categoryId] ?? null;

                return new VoteCategoryViewModel(
                    id: $categoryId,
                    title: (string) $category->title,
                    description: $category->description,
                    entries: SeededShuffle::order($byCategory[$categoryId] ?? [], $userId, $categoryId),
                    myVoteEntryId: $vote === null ? null : (int) $vote,
                );
            })
            ->all();
    }

    /**
     * Cast or change this reader's ballot in one category.
     *
     * Changing a vote **updates** the sitting row, which is why the unique index
     * on `(category_id, user_id)` is expressible here where it was not on the
     * entries table: a reader holds at most one ballot per category, enforced by
     * the database and not merely by this method.
     *
     * A reader may vote for their own entry (decision #3) — nothing here looks
     * at who submitted what. Not voting is not a state to record: there is no
     * abstention row (assumption A10).
     *
     * The phase is read from the **category's own** contest, so a request that
     * borrows another activity's URL prefix is still judged against the contest
     * it actually votes in.
     *
     * @throws VoteRefusedException on any forged or out-of-phase request
     */
    public function castVote(int $categoryId, int $entryId, int $userId): void
    {
        $category = QuoteContestCategory::query()->find($categoryId);

        if ($category === null) {
            throw VoteRefusedException::unknownCategory();
        }

        $this->assertVotesOpen((int) $category->activity_id);

        $entry = QuoteContestEntry::query()
            ->where('category_id', $categoryId)
            ->whereNull('withdrawn_at')
            ->find($entryId);

        if ($entry === null) {
            throw VoteRefusedException::unknownEntry();
        }

        QuoteContestVote::query()->updateOrCreate(
            ['category_id' => $categoryId, 'user_id' => $userId],
            ['entry_id' => $entryId],
        );
    }

    /**
     * @throws VoteRefusedException unless the contest is taking votes
     */
    private function assertVotesOpen(int $activityId): void
    {
        $activity = Activity::query()->find($activityId);
        $settings = $this->config->settingsFor($activityId);

        if ($activity === null || $settings === null) {
            throw VoteRefusedException::outsideVotePhase();
        }

        if ($this->phases->phaseFor($activity, $settings) !== QuoteContestPhase::Voting) {
            throw VoteRefusedException::outsideVotePhase();
        }
    }

    /**
     * Display names for every author of every entry, in one batched read.
     *
     * @param iterable<QuoteContestEntry> $entries
     * @return array<int, string> keyed by user id; a deleted author is absent
     */
    private function authorNames(iterable $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            foreach ((array) $entry->author_user_ids as $authorId) {
                $ids[(int) $authorId] = true;
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach ($this->profiles->getPublicProfiles(array_keys($ids)) as $userId => $profile) {
            // A deleted author comes back as a null DTO: no name to show, and
            // the entry stands without them (decision #19).
            if ($profile !== null) {
                $names[(int) $userId] = (string) $profile->display_name;
            }
        }

        return $names;
    }

    /** @param array<int, string> $names */
    private function toViewModel(QuoteContestEntry $entry, array $names): VoteEntryViewModel
    {
        $authorNames = [];
        foreach ((array) $entry->author_user_ids as $authorId) {
            // An author whose profile no longer resolves is omitted, and the
            // entry stands (decision #19).
            if (isset($names[(int) $authorId])) {
                $authorNames[] = $names[(int) $authorId];
            }
        }

        return new VoteEntryViewModel(
            id: (int) $entry->id,
            highlightedText: (string) $entry->highlighted_text,
            storyTitle: (string) $entry->story_title,
            storyUrl: route('stories.show', ['slug' => $entry->story_slug]),
            chapterTitle: (string) $entry->chapter_title,
            chapterUrl: route('chapters.show', [
                'storySlug' => $entry->story_slug,
                'chapterSlug' => $entry->chapter_slug,
            ]),
            authorNames: $authorNames,
        );
    }
}
