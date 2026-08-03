<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Support;

/**
 * The five states a Concours de citations can be in.
 *
 * Only {@see \App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestPhaseService}
 * produces one: nothing else in the feature derives a phase from raw dates.
 */
enum QuoteContestPhase
{
    /** The activity has not started: description and categories, read-only. */
    case BeforeStart;

    /** Readers may submit, replace and withdraw entries. */
    case Submissions;

    /** Submissions are closed, votes have not opened yet. */
    case Interlude;

    /** Readers may cast and change their ballots. */
    case Voting;

    /** The activity is over: everything read-only. */
    case Ended;
}
