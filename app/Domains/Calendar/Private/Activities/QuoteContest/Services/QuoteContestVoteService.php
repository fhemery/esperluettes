<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SeededShuffle;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\VoteRefusedException;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\ResultsCategoryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\ResultsEntryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VoteCategoryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VoteEntryViewModel;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;

/**
 * The reader's ballot: what they may vote for, and their one choice per
 * category.
 *
 * Two rules shape every query here. A privacy-withdrawn row still exists, but
 * it is out of every listing and every count (§2.3). And **no submitter
 * identity and no vote count ever leaves this service towards a reader**: the
 * ballot's view models have nowhere to put them (§3.3), which is what makes the
 * anonymity of decision #2 a query-shape guarantee rather than a template
 * convention.
 *
 * `resultsFor()` is the single, deliberate exception: it serves the moderator
 * tally of §4 and returns a different family of view models, which the caller
 * only builds for a moderator or an admin.
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
     * The moderator tally: every category of one contest with every entry it
     * ever held — withdrawn ones included, flagged as such — and the votes each
     * live entry scored.
     *
     * This is the one read of the contest that carries a submitter identity and
     * a vote count, and the only caller is the *Résultats* tab (§4). The count
     * is a `GROUP BY entry_id` computed on read: no denormalised counter, whose
     * only reader would be this handful of moderators (tradeoff 8).
     *
     * Withdrawn entries are excluded from the tally, not from the listing — a
     * privacy withdrawal drops the votes from the *count*, not from the table
     * (decision #18), so an accidental visibility toggle stays recoverable.
     *
     * Four queries whatever the size of the contest: the categories, their
     * entries, one grouped count, and one batched profile read covering both
     * the story authors and the submitters.
     *
     * @return array<int, ResultsCategoryViewModel>
     */
    public function resultsFor(int $activityId): array
    {
        $categories = $this->config->categoriesFor($activityId);

        if ($categories->isEmpty()) {
            return [];
        }

        $categoryIds = $categories->pluck('id')->map(intval(...))->all();

        $entries = QuoteContestEntry::query()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('id')
            ->get();

        $liveEntryIds = $entries
            ->filter(static fn (QuoteContestEntry $entry) => $entry->withdrawn_at === null)
            ->map(static fn (QuoteContestEntry $entry) => (int) $entry->id)
            ->values()
            ->all();

        $counts = $liveEntryIds === []
            ? []
            : QuoteContestVote::query()
                ->whereIn('entry_id', $liveEntryIds)
                ->groupBy('entry_id')
                ->selectRaw('entry_id, COUNT(*) as vote_count')
                ->pluck('vote_count', 'entry_id')
                ->all();

        $profiles = $this->resolveProfiles($this->profileIdsOf($entries));

        $byCategory = [];
        foreach ($entries as $entry) {
            $byCategory[(int) $entry->category_id][] = $this->toResultViewModel(
                $entry,
                (int) ($counts[(int) $entry->id] ?? 0),
                $profiles,
            );
        }

        return $categories
            ->map(function (QuoteContestCategory $category) use ($byCategory) {
                $entries = $byCategory[(int) $category->id] ?? [];

                // Highest score first; ties keep submission order, which the
                // query already gave us.
                usort(
                    $entries,
                    static fn (ResultsEntryViewModel $a, ResultsEntryViewModel $b) => $b->voteCount <=> $a->voteCount,
                );

                return new ResultsCategoryViewModel(
                    id: (int) $category->id,
                    title: (string) $category->title,
                    entries: $entries,
                );
            })
            ->all();
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

        return array_map(
            static fn (ProfileDto $profile) => (string) $profile->display_name,
            $this->resolveProfiles(array_keys($ids)),
        );
    }

    /**
     * Every user an entry names: the story's authors, and — for the moderator
     * tally alone — its submitter.
     *
     * @param iterable<QuoteContestEntry> $entries
     * @return array<int, int>
     */
    private function profileIdsOf(iterable $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            foreach ((array) $entry->author_user_ids as $authorId) {
                $ids[(int) $authorId] = true;
            }
            $ids[(int) $entry->user_id] = true;
        }

        return array_keys($ids);
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, ProfileDto> keyed by user id; a deleted user is absent
     */
    private function resolveProfiles(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $resolved = [];
        foreach ($this->profiles->getPublicProfiles($userIds) as $userId => $profile) {
            // A deleted user comes back as a null DTO: nothing to name, and the
            // entry stands without them (decision #19, decision #7).
            if ($profile !== null) {
                $resolved[(int) $userId] = $profile;
            }
        }

        return $resolved;
    }

    /** @param array<int, ProfileDto> $profiles */
    private function toResultViewModel(QuoteContestEntry $entry, int $voteCount, array $profiles): ResultsEntryViewModel
    {
        $authorNames = [];
        foreach ((array) $entry->author_user_ids as $authorId) {
            if (isset($profiles[(int) $authorId])) {
                $authorNames[] = (string) $profiles[(int) $authorId]->display_name;
            }
        }

        $submitter = $profiles[(int) $entry->user_id] ?? null;

        return new ResultsEntryViewModel(
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
            voteCount: $voteCount,
            submitterName: $submitter === null ? null : (string) $submitter->display_name,
            submitterUrl: $submitter === null ? null : route('profile.show', ['profile' => $submitter->slug]),
            isWithdrawn: $entry->withdrawn_at !== null,
        );
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
