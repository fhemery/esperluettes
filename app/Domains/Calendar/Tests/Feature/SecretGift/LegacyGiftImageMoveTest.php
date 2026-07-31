<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Support\LegacyGiftImageMover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** Put a legacy gift image on the `local` disk and point the assignment at it. */
function giveLegacyGiftImage(SecretGiftAssignment $assignment, string $bytes = 'legacy-gift-bytes'): string
{
    $path = "calendar/secret-gift/{$assignment->activity_id}/{$assignment->giver_user_id}.jpg";
    Storage::disk('local')->put($path, $bytes);

    $assignment->gift_image_path = $path;
    $assignment->save();

    return $path;
}

describe('SecretGift - Legacy gift image move', function () {
    beforeEach(function () {
        Storage::fake('local');
        Storage::fake('private');
    });

    it('moves a legacy local gift image onto the private disk and rewrites the row', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $legacyPath = giveLegacyGiftImage($assignment);

        $report = app(LegacyGiftImageMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe("secret-gift/{$result->id}/{$user1->id}.jpg");
        expect($report['moved'])->toBe(1);
        Storage::disk('private')->assertExists($assignment->gift_image_path);
        expect(Storage::disk('private')->get($assignment->gift_image_path))->toBe('legacy-gift-bytes');
        Storage::disk('local')->assertMissing($legacyPath);
    });

    it('is idempotent', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        giveLegacyGiftImage($assignment);

        $mover = app(LegacyGiftImageMover::class);
        $mover->toMedia();
        $assignment->refresh();
        $movedPath = $assignment->gift_image_path;

        $second = $mover->toMedia();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe($movedPath);
        expect($second['moved'])->toBe(0);
        expect($second['already_migrated'])->toBe(1);
        expect(Storage::disk('private')->allFiles())->toBe([$movedPath]);
    });

    it('leaves an already migrated row alone', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

        $path = "secret-gift/{$result->id}/already.jpg";
        Storage::disk('private')->put($path, 'already-there');
        $assignment->gift_image_path = $path;
        $assignment->save();

        $report = app(LegacyGiftImageMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe($path);
        expect($report['already_migrated'])->toBe(1);
        expect($report['moved'])->toBe(0);
    });

    it('reports a row whose source file is missing instead of failing', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

        $assignment->gift_image_path = "calendar/secret-gift/{$result->id}/{$user1->id}.jpg";
        $assignment->save();

        $report = app(LegacyGiftImageMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe("calendar/secret-gift/{$result->id}/{$user1->id}.jpg");
        expect($report['moved'])->toBe(0);
        expect($report['missing'])->toHaveKey($assignment->id);
    });

    it('moves the image back on rollback', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $legacyPath = giveLegacyGiftImage($assignment);

        $mover = app(LegacyGiftImageMover::class);
        $mover->toMedia();
        $assignment->refresh();
        $movedPath = $assignment->gift_image_path;

        $report = $mover->toLegacy();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe($legacyPath);
        expect($report['moved'])->toBe(1);
        Storage::disk('local')->assertExists($legacyPath);
        expect(Storage::disk('local')->get($legacyPath))->toBe('legacy-gift-bytes');
        Storage::disk('private')->assertMissing($movedPath);
    });

    it('serves a migrated image through the existing route', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        giveLegacyGiftImage($assignment);

        app(LegacyGiftImageMover::class)->toMedia();
        $assignment->refresh();

        $this->actingAs($user1);
        $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

        $response->assertStatus(200);
        expect($response->streamedContent())->toBe('legacy-gift-bytes');
    });
});
