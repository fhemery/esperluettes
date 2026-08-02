<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestSettings;
use App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The payload the admin activity form posts for a contest: the generic activity
 * fields plus the plugin's own `quote_contest[...]` block.
 */
function contestFormPayload(array $overrides = [], array $config = []): array
{
    return array_merge([
        'name' => 'Concours de citations',
        'activity_type' => QuoteContestRegistration::ACTIVITY_TYPE,
        'active_starts_at' => '2026-09-01T10:00',
        'active_ends_at' => '2026-10-01T10:00',
        'quote_contest' => array_merge([
            'submissions_end_at' => '2026-09-10T10:00',
            'votes_start_at' => '2026-09-15T10:00',
        ], $config),
    ], $overrides);
}

describe('Quote contest admin configuration', function () {

    it('persists the settings row atomically when the contest is created', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload())
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity = Activity::query()->firstOrFail();
        $settings = QuoteContestSettings::query()->firstOrFail();

        expect($settings->activity_id)->toBe($activity->id)
            ->and($settings->submissions_end_at->format('Y-m-d H:i'))->toBe('2026-09-10 10:00')
            ->and($settings->votes_start_at->format('Y-m-d H:i'))->toBe('2026-09-15 10:00');
    });

    it('refuses a submissions end before the activity start, in French, and creates nothing', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload(config: [
                'submissions_end_at' => '2026-08-20T10:00',
                'votes_start_at' => '2026-09-15T10:00',
            ]))
            ->assertSessionHasErrors([
                'quote_contest.submissions_end_at' => 'quote-contest::quote-contest.validation.submissions_end_before_activity_start',
            ]);

        expect(Activity::query()->count())->toBe(0)
            ->and(QuoteContestSettings::query()->count())->toBe(0);
    });

    it('refuses a votes start before the submissions end, in French', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload(config: [
                'submissions_end_at' => '2026-09-20T10:00',
                'votes_start_at' => '2026-09-15T10:00',
            ]))
            ->assertSessionHasErrors([
                'quote_contest.votes_start_at' => 'quote-contest::quote-contest.validation.votes_start_before_submissions_end',
            ]);

        expect(Activity::query()->count())->toBe(0);
    });

    it('refuses a votes start after the activity end, in French', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload(config: [
                'submissions_end_at' => '2026-09-10T10:00',
                'votes_start_at' => '2026-10-15T10:00',
            ]))
            ->assertSessionHasErrors([
                'quote_contest.votes_start_at' => 'quote-contest::quote-contest.validation.votes_start_after_activity_end',
            ]);

        expect(Activity::query()->count())->toBe(0);
    });

    it('accepts a contest with no interlude at all', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload(config: [
                'submissions_end_at' => '2026-09-12T08:30',
                'votes_start_at' => '2026-09-12T08:30',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        $settings = QuoteContestSettings::query()->firstOrFail();

        expect($settings->submissions_end_at->equalTo($settings->votes_start_at))->toBeTrue();
    });

    it('requires both contest dates', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload(overrides: ['quote_contest' => []]))
            ->assertSessionHasErrors(['quote_contest.submissions_end_at', 'quote_contest.votes_start_at']);

        expect(Activity::query()->count())->toBe(0);
    });

    it('updates the settings dates when the activity is edited', function () {
        $contest = createQuoteContest($this);
        $activity = Activity::findOrFail($contest->id);

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Concours de citations',
                'active_starts_at' => '2026-09-01T10:00',
                'active_ends_at' => '2026-10-01T10:00',
                'quote_contest' => [
                    'submissions_end_at' => '2026-09-11T12:00',
                    'votes_start_at' => '2026-09-14T12:00',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        $settings = QuoteContestSettings::query()->where('activity_id', $contest->id)->firstOrFail();

        expect(QuoteContestSettings::query()->count())->toBe(1)
            ->and($settings->submissions_end_at->format('Y-m-d H:i'))->toBe('2026-09-11 12:00')
            ->and($settings->votes_start_at->format('Y-m-d H:i'))->toBe('2026-09-14 12:00');
    });

    it('leaves the notification markers alone when the dates are edited', function () {
        $contest = createQuoteContest($this);
        $activity = Activity::findOrFail($contest->id);

        $firedAt = now()->subDays(2)->startOfSecond();
        QuoteContestSettings::query()->where('activity_id', $contest->id)->update([
            'notified_submissions_open_at' => $firedAt,
            'notified_submissions_closing_at' => $firedAt,
            'notified_votes_open_at' => $firedAt,
            'notified_votes_closing_at' => $firedAt,
        ]);

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Concours de citations',
                'active_starts_at' => '2026-09-01T10:00',
                'active_ends_at' => '2026-10-01T10:00',
                'quote_contest' => [
                    'submissions_end_at' => '2026-09-11T12:00',
                    'votes_start_at' => '2026-09-14T12:00',
                ],
            ])
            ->assertSessionHasNoErrors();

        $settings = QuoteContestSettings::query()->where('activity_id', $contest->id)->firstOrFail();

        expect($settings->notified_submissions_open_at->format('Y-m-d H:i:s'))->toBe($firedAt->format('Y-m-d H:i:s'))
            ->and($settings->notified_submissions_closing_at->format('Y-m-d H:i:s'))->toBe($firedAt->format('Y-m-d H:i:s'))
            ->and($settings->notified_votes_open_at->format('Y-m-d H:i:s'))->toBe($firedAt->format('Y-m-d H:i:s'))
            ->and($settings->notified_votes_closing_at->format('Y-m-d H:i:s'))->toBe($firedAt->format('Y-m-d H:i:s'));
    });

    it('leaves the other activity types free of contest rules', function () {
        registerFakeActivityType(app(\App\Domains\Calendar\Public\Api\CalendarRegistry::class));

        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), [
                'name' => 'Sans config',
                'activity_type' => 'fake',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        expect(QuoteContestSettings::query()->count())->toBe(0);
    });

    it('treats a contest with zero categories as a valid draft', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), contestFormPayload())
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity = Activity::query()->firstOrFail();

        expect(QuoteContestCategory::query()->count())->toBe(0);

        $this->get(route('calendar.admin.activities.edit', $activity))
            ->assertOk()
            ->assertSee('quote-contest::quote-contest.config.categories_empty', false);
    });

    it('words every configuration message in French', function () {
        $fr = fn (string $key) => trans('quote-contest::quote-contest.' . $key, [], 'fr');

        expect($fr('validation.submissions_end_before_activity_start'))
            ->toBe('La fin des soumissions ne peut pas précéder le début de l\'activité.')
            ->and($fr('validation.votes_start_before_submissions_end'))
            ->toBe('Le début des votes ne peut pas précéder la fin des soumissions.')
            ->and($fr('validation.votes_start_after_activity_end'))
            ->toBe('Le début des votes ne peut pas dépasser la fin de l\'activité.')
            ->and($fr('validation.invalid_date'))
            ->toBe('Cette date n\'est pas valide.')
            ->and($fr('config.section_title'))->toBe('Concours de citations')
            ->and($fr('config.categories_empty'))
            ->toBe('Aucune catégorie pour l\'instant : le concours reste un brouillon valide, mais les participants n\'auront rien à quoi soumettre.');
    });

    it('shows the stored contest dates on the edit form', function () {
        $contest = createQuoteContest($this, settings: [
            'submissions_end_at' => '2026-09-11 12:00:00',
            'votes_start_at' => '2026-09-14 12:00:00',
        ]);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($contest->id)))
            ->assertOk()
            ->assertSee('quote_contest[submissions_end_at]', false)
            ->assertSee('2026-09-11T12:00', false)
            ->assertSee('2026-09-14T12:00', false);
    });
});
