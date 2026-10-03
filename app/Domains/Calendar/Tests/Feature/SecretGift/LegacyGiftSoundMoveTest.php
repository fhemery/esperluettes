<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Support\LegacyGiftSoundMover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** Put a legacy gift sound on the `local` disk and point the assignment at it. */
function giveLegacyGiftSound(SecretGiftAssignment $assignment, string $bytes = 'legacy-sound-bytes'): string
{
    $path = "calendar/secret-gift/{$assignment->activity_id}/sound-{$assignment->giver_user_id}-1700000000.mp3";
    Storage::disk('local')->put($path, $bytes);

    $assignment->gift_sound_path = $path;
    $assignment->save();

    return $path;
}

describe('SecretGift - Legacy gift sound move', function () {
    beforeEach(function () {
        Storage::fake('local');
        Storage::fake('private');
    });

    it('moves a legacy local gift sound onto the private disk and rewrites the row', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $legacyPath = giveLegacyGiftSound($assignment);

        $report = app(LegacyGiftSoundMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_sound_path)->toBe("secret-gift/{$result->id}/sound-{$user1->id}-1700000000.mp3");
        expect($report['moved'])->toBe(1);
        Storage::disk('private')->assertExists($assignment->gift_sound_path);
        expect(Storage::disk('private')->get($assignment->gift_sound_path))->toBe('legacy-sound-bytes');
        Storage::disk('local')->assertMissing($legacyPath);
    });

    it('is idempotent', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        giveLegacyGiftSound($assignment);

        $mover = app(LegacyGiftSoundMover::class);
        $mover->toMedia();
        $assignment->refresh();
        $movedPath = $assignment->gift_sound_path;

        $second = $mover->toMedia();

        $assignment->refresh();
        expect($assignment->gift_sound_path)->toBe($movedPath);
        expect($second['moved'])->toBe(0);
        expect($second['already_migrated'])->toBe(1);
        expect(Storage::disk('private')->allFiles())->toBe([$movedPath]);
    });

    it('reports a row whose file is missing without failing', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

        $legacyPath = "calendar/secret-gift/{$result->id}/sound-{$user1->id}-1700000000.mp3";
        $assignment->gift_sound_path = $legacyPath;
        $assignment->save();

        $report = app(LegacyGiftSoundMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_sound_path)->toBe($legacyPath);
        expect($report['moved'])->toBe(0);
        expect($report['missing'])->toBe([$assignment->id => $legacyPath]);
    });

    it('leaves rows without a sound alone', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

        $report = app(LegacyGiftSoundMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_sound_path)->toBeNull();
        expect($report)->toBe(['moved' => 0, 'already_migrated' => 0, 'missing' => []]);
        expect(Storage::disk('private')->allFiles())->toBe([]);
    });

    it('moves sounds back on toLegacy', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        $legacyPath = giveLegacyGiftSound($assignment);

        $mover = app(LegacyGiftSoundMover::class);
        $mover->toMedia();
        $assignment->refresh();
        $movedPath = $assignment->gift_sound_path;

        $report = $mover->toLegacy();

        $assignment->refresh();
        expect($assignment->gift_sound_path)->toBe($legacyPath);
        expect($report['moved'])->toBe(1);
        Storage::disk('local')->assertExists($legacyPath);
        expect(Storage::disk('local')->get($legacyPath))->toBe('legacy-sound-bytes');
        Storage::disk('private')->assertMissing($movedPath);
    });

    it('does not touch gift image paths', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

        $imagePath = "calendar/secret-gift/{$result->id}/{$user1->id}.jpg";
        Storage::disk('local')->put($imagePath, 'legacy-image-bytes');
        $assignment->gift_image_path = $imagePath;
        $assignment->save();
        giveLegacyGiftSound($assignment);

        app(LegacyGiftSoundMover::class)->toMedia();

        $assignment->refresh();
        expect($assignment->gift_image_path)->toBe($imagePath);
        expect($assignment->gift_sound_path)->toBe("secret-gift/{$result->id}/sound-{$user1->id}-1700000000.mp3");
        Storage::disk('local')->assertExists($imagePath);
    });

    it('serves a migrated legacy sound through the sound route', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        giveLegacyGiftSound($assignment);

        app(LegacyGiftSoundMover::class)->toMedia();

        $this->actingAs($user1);
        $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

        $response->assertStatus(200);
        expect($response->streamedContent())->toBe('legacy-sound-bytes');
    });
});
