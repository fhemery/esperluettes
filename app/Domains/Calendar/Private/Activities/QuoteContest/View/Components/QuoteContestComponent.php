<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Components;

use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestConfigService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestPhaseService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestVoteService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\ContestCategoryViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\MyQuotesViewModel;
use App\Domains\Calendar\Private\Activities\QuoteContest\View\Models\VotesViewModel;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * The contest's reader page: the tabs array is built here, server-side, and
 * each tab receives a view model built for it.
 *
 * A tab a reader may not see is *absent* from the array, never rendered and
 * then hidden (architecture §3.3, point 4). For now the array holds
 * *Mes citations* and *Votes*; *Résultats*, which only moderators see, arrives
 * with the phase that implements it.
 */
class QuoteContestComponent extends Component
{
    public function __construct(
        public Activity $activity,
        private readonly QuoteContestConfigService $config,
        private readonly QuoteContestPhaseService $phases,
        private readonly QuoteContestSubmissionService $submissions,
        private readonly QuoteContestVoteService $votes,
    ) {}

    public function render(): View
    {
        $userId = (int) Auth::id();
        $activityId = (int) $this->activity->id;

        $settings = $this->config->settingsFor($activityId);

        // An unconfigured contest has not begun — the same reading as A15's
        // null activity dates. Phase 4 makes the settings row mandatory at
        // creation, so this is a guard, not a supported state.
        $phase = $settings === null
            ? QuoteContestPhase::BeforeStart
            : $this->phases->phaseFor($this->activity, $settings);

        $myEntries = $this->submissions->currentEntriesFor($activityId, $userId);

        $categories = $this->config->categoriesFor($activityId)
            ->map(fn ($category) => new ContestCategoryViewModel(
                id: (int) $category->id,
                title: (string) $category->title,
                description: $category->description,
                myEntry: $myEntries[(int) $category->id] ?? null,
            ))
            ->all();

        $myQuotes = new MyQuotesViewModel(
            activityId: $activityId,
            phase: $phase,
            categories: $categories,
            // Outside the submission phase there is nothing to pick, so the
            // reader's quote book is not read at all.
            quotes: $phase === QuoteContestPhase::Submissions
                ? $this->submissions->pickerFor($userId)
                : [],
            submissionsStartAt: $this->activity->active_starts_at,
            submissionsEndAt: $settings?->submissions_end_at,
            votesStartAt: $settings?->votes_start_at,
        );

        $votes = new VotesViewModel(
            activityId: $activityId,
            phase: $phase,
            // Before the votes open there is nothing to choose between, so the
            // ballot is not built at all — the same economy as the picker's.
            categories: in_array($phase, [QuoteContestPhase::Voting, QuoteContestPhase::Ended], true)
                ? $this->votes->ballotFor($activityId, $userId)
                : [],
            votesStartAt: $settings?->votes_start_at,
            votesEndAt: $this->activity->active_ends_at,
        );

        return view('quote-contest::components.quote-contest', [
            'activity' => $this->activity,
            // The tab keys are the URL hash fragments, so a notification can
            // deep-link straight to `#votes`.
            'tabs' => [
                ['key' => 'my-quotes', 'label' => __('quote-contest::quote-contest.tab_my_quotes')],
                ['key' => 'votes', 'label' => __('quote-contest::quote-contest.tab_votes')],
            ],
            'myQuotes' => $myQuotes,
            'votes' => $votes,
        ]);
    }
}
