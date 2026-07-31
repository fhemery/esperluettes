<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Support\SecretGiftMediaUsageProvider;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

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
