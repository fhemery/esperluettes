<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Tests\TestCase;

/**
 * Create a Concours de citations activity with its settings row.
 *
 * Default timeline puts the contest in its submission period.
 * Returns an object: { id: int, url: string, activity: Activity, settings: QuoteContestSettings }
 */
function createQuoteContest(TestCase $t, array $overrides = [], array $settings = [], ?int $actorUserId = null): object
{
    $baseOverrides = [
        'name' => 'Concours de citations',
        'activity_type' => QuoteContestRegistration::ACTIVITY_TYPE,
        'role_restrictions' => [Roles::USER_CONFIRMED],
        'preview_starts_at' => now()->subDays(3),
        'active_starts_at' => now()->subDay(),
        'active_ends_at' => now()->addDays(20),
    ];

    $id = createActivity($t, overrides: array_merge($baseOverrides, $overrides), actorUserId: $actorUserId);
    $activity = Activity::findOrFail($id);

    $settingsRow = QuoteContestSettings::create(array_merge([
        'activity_id' => $id,
        'submissions_end_at' => now()->addDays(5),
        'votes_start_at' => now()->addDays(10),
    ], $settings));

    return (object) [
        'id' => $id,
        'url' => route('calendar.activities.show', $activity->slug),
        'activity' => $activity,
        'settings' => $settingsRow,
    ];
}

/**
 * The five phase shortcuts below move the real dates around `now()` rather than
 * stubbing the phase service: a phase is only ever derived from the clock.
 */
function createContestBeforeStart(TestCase $t, array $overrides = [], array $settings = []): object
{
    return createQuoteContest($t, array_merge([
        'preview_starts_at' => now()->subDay(),
        'active_starts_at' => now()->addDays(2),
        'active_ends_at' => now()->addDays(30),
    ], $overrides), array_merge([
        'submissions_end_at' => now()->addDays(12),
        'votes_start_at' => now()->addDays(15),
    ], $settings));
}

function createContestInSubmissions(TestCase $t, array $overrides = [], array $settings = []): object
{
    return createQuoteContest($t, $overrides, $settings);
}

function createContestInInterlude(TestCase $t, array $overrides = [], array $settings = []): object
{
    return createQuoteContest($t, array_merge([
        'preview_starts_at' => now()->subDays(12),
        'active_starts_at' => now()->subDays(10),
        'active_ends_at' => now()->addDays(10),
    ], $overrides), array_merge([
        'submissions_end_at' => now()->subDay(),
        'votes_start_at' => now()->addDays(2),
    ], $settings));
}

function createContestInVoting(TestCase $t, array $overrides = [], array $settings = []): object
{
    return createQuoteContest($t, array_merge([
        'preview_starts_at' => now()->subDays(12),
        'active_starts_at' => now()->subDays(10),
        'active_ends_at' => now()->addDays(5),
    ], $overrides), array_merge([
        'submissions_end_at' => now()->subDays(3),
        'votes_start_at' => now()->subDay(),
    ], $settings));
}

function createContestEnded(TestCase $t, array $overrides = [], array $settings = []): object
{
    return createQuoteContest($t, array_merge([
        'preview_starts_at' => now()->subDays(30),
        'active_starts_at' => now()->subDays(25),
        'active_ends_at' => now()->subDay(),
    ], $overrides), array_merge([
        'submissions_end_at' => now()->subDays(15),
        'votes_start_at' => now()->subDays(10),
    ], $settings));
}
