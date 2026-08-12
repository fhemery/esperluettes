<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftSettings;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function isSecretGiftRegistrationOpen(int $activityId): bool
{
    return app(SecretGiftConfigService::class)->isRegistrationOpen(Activity::findOrFail($activityId));
}

describe('Secret Gift registration window', function () {

    it('reports registration open during preview before the deadline', function () {
        $gift = createPreviewSecretGift($this);

        expect(isSecretGiftRegistrationOpen($gift->id))->toBeTrue();
    });

    it('reports registration closed once the deadline has passed', function () {
        $gift = createPreviewSecretGift($this, settings: ['registration_ends_at' => now()->subMinute()]);

        expect(isSecretGiftRegistrationOpen($gift->id))->toBeFalse();
    });

    it('reports registration closed once the activity has been shuffled', function () {
        $gift = createPreviewSecretGift($this);
        registerSecretGiftParticipants($gift->id, [alice($this)->id, bob($this)->id]);
        shuffleSecretGift($gift->activity);

        expect(isSecretGiftRegistrationOpen($gift->id))->toBeFalse();
    });

    it('reports registration closed once the activity is active', function () {
        // Deadline still in the future: only the state closes the window.
        $gift = createActiveSecretGift($this, settings: ['registration_ends_at' => now()->addDay()]);

        expect(isSecretGiftRegistrationOpen($gift->id))->toBeFalse();
    });

    it('reports registration closed when no settings row exists', function () {
        $gift = createPreviewSecretGift($this);
        SecretGiftSettings::query()->where('activity_id', $gift->id)->delete();

        expect(isSecretGiftRegistrationOpen($gift->id))->toBeFalse();
    });
});
