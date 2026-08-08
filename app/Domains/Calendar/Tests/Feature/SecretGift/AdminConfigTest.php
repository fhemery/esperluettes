<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftSettings;
use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The payload the admin activity form posts for a Secret Gift: the generic
 * activity fields plus the plugin's own `secret_gift[...]` block.
 */
function secretGiftFormPayload(array $overrides = [], array $config = []): array
{
    return array_merge([
        'name' => 'Cadeau surprise',
        'activity_type' => SecretGiftRegistration::ACTIVITY_TYPE,
        'preview_starts_at' => '2026-09-01T10:00',
        'active_starts_at' => '2026-10-01T10:00',
        'active_ends_at' => '2026-10-15T10:00',
        'secret_gift' => array_merge([
            'registration_ends_at' => '2026-09-20T10:00',
        ], $config),
    ], $overrides);
}

describe('Secret Gift admin configuration', function () {

    it('persists the settings row atomically when the activity is created', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), secretGiftFormPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        $activity = Activity::query()->firstOrFail();
        $settings = SecretGiftSettings::query()->firstOrFail();

        expect(SecretGiftSettings::query()->count())->toBe(1)
            ->and($settings->activity_id)->toBe($activity->id)
            ->and($settings->registration_ends_at->format('Y-m-d H:i'))->toBe('2026-09-20 10:00');
    });

    it('updates the settings row when the activity is edited', function () {
        $gift = createActiveSecretGift($this);
        $activity = Activity::findOrFail($gift->id);

        $this->actingAs(admin($this))
            ->put(route('calendar.admin.activities.update', $activity), [
                'name' => 'Cadeau surprise',
                'preview_starts_at' => '2026-09-01T10:00',
                'active_starts_at' => '2026-10-01T10:00',
                'active_ends_at' => '2026-10-15T10:00',
                'secret_gift' => ['registration_ends_at' => '2026-09-25T18:30'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        $settings = SecretGiftSettings::query()->where('activity_id', $gift->id)->firstOrFail();

        expect(SecretGiftSettings::query()->count())->toBe(1)
            ->and($settings->registration_ends_at->format('Y-m-d H:i'))->toBe('2026-09-25 18:30');
    });

    it('refuses a missing registration deadline and creates nothing', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), secretGiftFormPayload(overrides: ['secret_gift' => []]))
            ->assertSessionHasErrors(['secret_gift.registration_ends_at']);

        expect(Activity::query()->count())->toBe(0)
            ->and(SecretGiftSettings::query()->count())->toBe(0);
    });

    it('refuses a deadline before the preview start, in French, and creates nothing', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), secretGiftFormPayload(config: [
                'registration_ends_at' => '2026-08-20T10:00',
            ]))
            ->assertSessionHasErrors([
                'secret_gift.registration_ends_at' => 'secret-gift::secret-gift.validation.registration_ends_before_preview_start',
            ]);

        expect(Activity::query()->count())->toBe(0)
            ->and(SecretGiftSettings::query()->count())->toBe(0);
    });

    it('refuses a deadline after the activity start, in French', function () {
        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), secretGiftFormPayload(config: [
                'registration_ends_at' => '2026-10-05T10:00',
            ]))
            ->assertSessionHasErrors([
                'secret_gift.registration_ends_at' => 'secret-gift::secret-gift.validation.registration_ends_after_activity_start',
            ]);

        expect(Activity::query()->count())->toBe(0);
    });

    it('renders the deadline field on the create form and prefills it on edit', function () {
        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.create'))
            ->assertOk()
            ->assertSee('secret_gift[registration_ends_at]', false);

        $gift = createActiveSecretGift($this, settings: ['registration_ends_at' => '2026-09-20 10:00:00']);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', Activity::findOrFail($gift->id)))
            ->assertOk()
            ->assertSee('secret_gift[registration_ends_at]', false)
            ->assertSee('2026-09-20T10:00', false);
    });

    it('cascades the settings row when the activity is deleted', function () {
        $gift = createActiveSecretGift($this);

        expect(SecretGiftSettings::query()->count())->toBe(1);

        $this->actingAs(admin($this))
            ->delete(route('calendar.admin.activities.destroy', Activity::findOrFail($gift->id)))
            ->assertRedirect(route('calendar.admin.activities.index'));

        expect(SecretGiftSettings::query()->count())->toBe(0);
    });

    it('leaves the other activity types free of secret gift rules', function () {
        registerFakeActivityType(app(\App\Domains\Calendar\Public\Api\CalendarRegistry::class));

        $this->actingAs(admin($this))
            ->post(route('calendar.admin.activities.store'), [
                'name' => 'Sans config',
                'activity_type' => 'fake',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.admin.activities.index'));

        expect(SecretGiftSettings::query()->count())->toBe(0);
    });

    it('words every configuration message in French', function () {
        $fr = fn (string $key) => trans('secret-gift::secret-gift.' . $key, [], 'fr');

        expect($fr('validation.registration_ends_before_preview_start'))
            ->toBe('La fin des inscriptions ne peut pas précéder l\'ouverture de l\'activité.')
            ->and($fr('validation.registration_ends_after_activity_start'))
            ->toBe('La fin des inscriptions ne peut pas dépasser le début de l\'activité.')
            ->and($fr('config.section_title'))->toBe('Cadeau surprise')
            ->and($fr('config.registration_ends_at'))->toBe('Fin des inscriptions');
    });
});
