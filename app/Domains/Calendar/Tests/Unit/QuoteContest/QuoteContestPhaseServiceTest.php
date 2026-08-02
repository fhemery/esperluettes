<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestPhaseService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use App\Domains\Calendar\Private\Models\Activity;
use Carbon\Carbon;
use Tests\TestCase;

// The models are never saved, but Eloquent still needs a booted application to
// cast their datetime attributes.
uses(TestCase::class);

/**
 * Reference timeline used by the whole table:
 *
 *   active_starts_at   2026-03-01 10:00   submissions open
 *   submissions_end_at 2026-03-10 10:00   submissions close, interlude opens
 *   votes_start_at     2026-03-15 10:00   votes open
 *   active_ends_at     2026-03-20 10:00   contest over
 *
 * Convention under test: an instant landing exactly on a boundary belongs to
 * the *later* phase.
 */
function quoteContestPhaseAt(string $now, array $dates = []): QuoteContestPhase
{
    $dates = array_merge([
        'active_starts_at' => '2026-03-01 10:00:00',
        'active_ends_at' => '2026-03-20 10:00:00',
        'submissions_end_at' => '2026-03-10 10:00:00',
        'votes_start_at' => '2026-03-15 10:00:00',
    ], $dates);

    Carbon::setTestNow(Carbon::parse($now));

    $activity = new Activity([
        'active_starts_at' => $dates['active_starts_at'],
        'active_ends_at' => $dates['active_ends_at'],
    ]);

    $settings = new QuoteContestSettings([
        'submissions_end_at' => $dates['submissions_end_at'],
        'votes_start_at' => $dates['votes_start_at'],
    ]);

    return (new QuoteContestPhaseService())->phaseFor($activity, $settings);
}

afterEach(function () {
    Carbon::setTestNow();
});

describe('QuoteContestPhaseService', function () {

    it('derives the phase at every boundary of the timeline', function (string $now, QuoteContestPhase $expected) {
        expect(quoteContestPhaseAt($now))->toBe($expected);
    })->with([
        'well before the start' => ['2026-02-01 00:00:00', QuoteContestPhase::BeforeStart],
        'one second before the start' => ['2026-03-01 09:59:59', QuoteContestPhase::BeforeStart],
        'exactly on active_starts_at' => ['2026-03-01 10:00:00', QuoteContestPhase::Submissions],
        'mid submissions' => ['2026-03-05 00:00:00', QuoteContestPhase::Submissions],
        'one second before submissions close' => ['2026-03-10 09:59:59', QuoteContestPhase::Submissions],
        'exactly on submissions_end_at' => ['2026-03-10 10:00:00', QuoteContestPhase::Interlude],
        'mid interlude' => ['2026-03-12 00:00:00', QuoteContestPhase::Interlude],
        'one second before the votes open' => ['2026-03-15 09:59:59', QuoteContestPhase::Interlude],
        'exactly on votes_start_at' => ['2026-03-15 10:00:00', QuoteContestPhase::Voting],
        'mid voting' => ['2026-03-17 00:00:00', QuoteContestPhase::Voting],
        'one second before the end' => ['2026-03-20 09:59:59', QuoteContestPhase::Voting],
        'exactly on active_ends_at' => ['2026-03-20 10:00:00', QuoteContestPhase::Ended],
        'well after the end' => ['2026-04-01 00:00:00', QuoteContestPhase::Ended],
    ]);

    it('never reports an interlude when the votes open as the submissions close', function () {
        $sameInstant = ['votes_start_at' => '2026-03-10 10:00:00'];

        expect(quoteContestPhaseAt('2026-03-10 09:59:59', $sameInstant))->toBe(QuoteContestPhase::Submissions)
            ->and(quoteContestPhaseAt('2026-03-10 10:00:00', $sameInstant))->toBe(QuoteContestPhase::Voting);
    });

    it('stays before the start while the activity has no start date', function () {
        // A start date is what opens the contest: without one nothing has begun,
        // even once the end date has gone by.
        expect(quoteContestPhaseAt('2026-04-01 00:00:00', ['active_starts_at' => null]))
            ->toBe(QuoteContestPhase::BeforeStart);
    });

    it('never ends while the activity has no end date', function () {
        expect(quoteContestPhaseAt('2030-01-01 00:00:00', ['active_ends_at' => null]))
            ->toBe(QuoteContestPhase::Voting);
    });
});
