<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Listeners;

use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use App\Domains\Story\Public\Contracts\StoryVisibility;
use App\Domains\Story\Public\Events\StoryExcludedFromEvents;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;

/**
 * A contest entry is a snapshot, so it outlives the quote it came from — but
 * not the right to read the passage. When the quoted story stops being
 * readable, its entries are withdrawn (§2.3).
 *
 * Both handlers funnel into one service call so the two paths cannot drift.
 */
final class WithdrawEntriesOnStoryIneligible
{
    public function __construct(
        private readonly QuoteContestSubmissionService $submissions,
    ) {}

    /**
     * The event carries the new visibility, so no Story read is needed here.
     * Eligible visibilities are `public` and `community` (assumption A2);
     * anything else withdraws. A story coming *back* is not restored.
     */
    public function handleVisibilityChanged(StoryVisibilityChanged $event): void
    {
        if (in_array($event->newVisibility, [StoryVisibility::PUBLIC, StoryVisibility::COMMUNITY], true)) {
            return;
        }

        $this->submissions->withdrawEntriesForStory($event->storyId);
    }

    public function handleExcludedFromEvents(StoryExcludedFromEvents $event): void
    {
        $this->submissions->withdrawEntriesForStory($event->storyId);
    }
}
