<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Support\SecretGiftMediaUsageProvider;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Media\Private\Services\MediaService;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function providerTestUsedPaths(): array
{
    $paths = [];
    foreach ((new SecretGiftMediaUsageProvider())->usedPaths() as $path) {
        $paths[] = $path;
    }
    return $paths;
}

function providerTestUploadSound(TestCase $test, Activity $activity): void
{
    $test->post(route('secret-gift.save-gift', $activity), [
        'gift_sound' => UploadedFile::fake()->create('gift.mp3', 100, 'audio/mpeg'),
    ]);
}

describe('SecretGift - Media usage provider', function () {
    it('reports every stored gift image path to the media registry', function () {
        $user1 = alice($this);
        $user2 = bob($this);
        $user3 = carol($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id, $user3->id]);

        $first = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $first->gift_image_path = 'secret-gift/' . $result->id . '/one.jpg';
        $first->save();

        $second = getSecretGiftAssignmentAsGiver($result->id, $user2->id);
        $second->gift_image_path = 'secret-gift/' . $result->id . '/two.jpg';
        $second->save();

        $paths = [];
        foreach ((new SecretGiftMediaUsageProvider())->usedPaths() as $path) {
            $paths[] = $path;
        }

        expect($paths)->toHaveCount(2)
            ->and($paths)->toContain('secret-gift/' . $result->id . '/one.jpg')
            ->and($paths)->toContain('secret-gift/' . $result->id . '/two.jpg');
    });

    it('registers the provider on the media usage registry at boot', function () {
        $providers = app(MediaUsageRegistry::class)->providers();

        $hasGiftProvider = collect($providers)
            ->contains(fn ($provider) => $provider instanceof SecretGiftMediaUsageProvider);

        expect($hasGiftProvider)->toBeTrue();
    });
});

describe('SecretGift - Media usage provider (sounds)', function () {
    beforeEach(function () {
        Storage::fake('local');
        Storage::fake('private');
        Storage::fake('public');
    });

    it('claims gift sound paths', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $assignment->gift_image_path = 'secret-gift/' . $result->id . '/one.jpg';
        $assignment->gift_sound_path = 'secret-gift/' . $result->id . '/one.mp3';
        $assignment->save();

        $other = getSecretGiftAssignmentAsGiver($result->id, $user2->id);
        $other->gift_sound_path = 'secret-gift/' . $result->id . '/two.mp3';
        $other->save();

        expect(providerTestUsedPaths())->toEqualCanonicalizing([
            'secret-gift/' . $result->id . '/one.jpg',
            'secret-gift/' . $result->id . '/one.mp3',
            'secret-gift/' . $result->id . '/two.mp3',
        ]);
    });

    it('stops claiming a sound once it is removed', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);
        providerTestUploadSound($this, $result->activity);
        $path = getSecretGiftAssignmentAsGiver($result->id, $user1->id)->gift_sound_path;
        expect(providerTestUsedPaths())->toContain($path);

        $this->post(route('secret-gift.save-gift', $result->activity), ['gift_sound_remove' => true]);

        expect(providerTestUsedPaths())->not->toContain($path);
    });

    it('lets media gc reclaim a replaced sound but keep the current one', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);
        providerTestUploadSound($this, $result->activity);
        $old = getSecretGiftAssignmentAsGiver($result->id, $user1->id)->gift_sound_path;

        providerTestUploadSound($this, $result->activity);
        $current = getSecretGiftAssignmentAsGiver($result->id, $user1->id)->gift_sound_path;
        expect($current)->not->toBe($old);

        $report = app(MediaService::class)->gc(-1);

        expect($report['deleted'])->toBe([$old]);
        Storage::disk('private')->assertMissing($old);
        Storage::disk('private')->assertExists($current);
    });

    it('lets media gc reclaim sounds of a re-shuffled activity', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $gone = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $kept = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);
        providerTestUploadSound($this, $gone->activity);
        providerTestUploadSound($this, $kept->activity);
        $orphan = getSecretGiftAssignmentAsGiver($gone->id, $user1->id)->gift_sound_path;
        $live = getSecretGiftAssignmentAsGiver($kept->id, $user1->id)->gift_sound_path;

        SecretGiftAssignment::query()->where('activity_id', $gone->id)->delete();

        $report = app(MediaService::class)->gc(-1);

        expect($report['deleted'])->toBe([$orphan]);
        Storage::disk('private')->assertMissing($orphan);
        Storage::disk('private')->assertExists($live);
    });
});
