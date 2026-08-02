<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Services;

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Models\Activity;
use Carbon\CarbonImmutable;

/**
 * The single source of truth for what phase a contest is in.
 *
 * Every screen and every write authorization asks this one question, so the
 * read-only states and the write guards can never disagree. Nothing else in the
 * feature may recompute a phase from raw dates.
 *
 * The timeline is built from four datetimes — the activity's own
 * `active_starts_at` / `active_ends_at` bound the contest, the settings'
 * `submissions_end_at` / `votes_start_at` cut it into periods:
 *
 *   active_starts_at ─── submissions_end_at ─── votes_start_at ─── active_ends_at
 *      Submissions            Interlude              Voting
 *
 * Convention: an instant landing exactly on a boundary belongs to the *later*
 * phase, which matches how `Activity::state` reads the same two activity dates.
 * When `submissions_end_at` equals `votes_start_at` the interlude simply never
 * occurs.
 */
class QuoteContestPhaseService
{
    public function phaseFor(Activity $activity, QuoteContestSettings $settings): QuoteContestPhase
    {
        $now = CarbonImmutable::now();

        $startsAt = $activity->active_starts_at;
        $endsAt = $activity->active_ends_at;

        // No start date means nothing has begun yet, elapsed end date or not.
        if ($startsAt === null || $startsAt->greaterThan($now)) {
            return QuoteContestPhase::BeforeStart;
        }

        // No end date means the contest never closes on its own.
        if ($endsAt !== null && $endsAt->lessThanOrEqualTo($now)) {
            return QuoteContestPhase::Ended;
        }

        if ($settings->submissions_end_at->greaterThan($now)) {
            return QuoteContestPhase::Submissions;
        }

        if ($settings->votes_start_at->greaterThan($now)) {
            return QuoteContestPhase::Interlude;
        }

        return QuoteContestPhase::Voting;
    }
}
