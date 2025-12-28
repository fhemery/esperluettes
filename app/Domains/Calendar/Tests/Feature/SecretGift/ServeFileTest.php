<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SecretGift - Serve Files', function () {
    beforeEach(function () {
        Storage::fake('local');
    });

    describe('Serve Sound', function () {
        it('allows giver to view their own sound', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload a sound file
            $file = UploadedFile::fake()->create('gift.mp3', 1000, 'audio/mpeg');
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_sound' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Try to view the sound
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type');
            $response->assertHeader('Cache-Control');
        });

        it('prevents recipient from viewing sound before activity ends', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload a sound file
            $file = UploadedFile::fake()->create('gift.mp3', 1000, 'audio/mpeg');
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_sound' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            // Recipient tries to view before activity ends
            $this->actingAs($user2);
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('allows recipient to view sound after activity ends', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload a sound file
            $file = UploadedFile::fake()->create('gift.mp3', 1000, 'audio/mpeg');
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_sound' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            // End the activity by setting active_ends_at to past
            $result->activity->update(['active_ends_at' => now()->subHour()]);
            $result->activity->refresh();

            // Recipient can now view
            $this->actingAs($user2);
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type');
        });

        it('prevents non-participants from viewing sound', function () {
            $user1 = alice($this);
            $user2 = bob($this);
            $outsider = carol($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload a sound file
            $file = UploadedFile::fake()->create('gift.mp3', 1000, 'audio/mpeg');
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_sound' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Outsider tries to view
            $this->actingAs($outsider);
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('returns 404 when sound file does not exist', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Manually set a non-existent path
            $assignment->gift_sound_path = 'non/existent/path.mp3';
            $assignment->save();

            $this->actingAs($user1);
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(404);
        });

        it('returns 404 when assignment has no sound', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            $this->actingAs($user1);
            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(404);
        });
    });

    describe('Serve Image', function () {
        it('allows giver to view their own image', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload an image file
            $file = UploadedFile::fake()->image('gift.jpg', 800, 600);
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Try to view the image
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('prevents recipient from viewing image before activity ends', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload an image file
            $file = UploadedFile::fake()->image('gift.jpg', 800, 600);
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            // Recipient tries to view before activity ends
            $this->actingAs($user2);
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('allows recipient to view image after activity ends', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload an image file
            $file = UploadedFile::fake()->image('gift.jpg', 800, 600);
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            // End the activity by setting active_ends_at to past
            $result->activity->update(['active_ends_at' => now()->subHour()]);
            $result->activity->refresh();

            // Recipient can now view
            $this->actingAs($user2);
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('prevents non-participants from viewing image', function () {
            $user1 = alice($this);
            $user2 = bob($this);
            $outsider = carol($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            // Upload an image file
            $file = UploadedFile::fake()->image('gift.jpg', 800, 600);
            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => $file,
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Outsider tries to view
            $this->actingAs($outsider);
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });
    });
});
