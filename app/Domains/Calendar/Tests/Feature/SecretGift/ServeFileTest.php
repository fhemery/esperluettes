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

function uploadServeTestSound(TestCase $test, $result, $giver): SecretGiftAssignment
{
    // A fake create() file is empty; give it bytes so a Range can be satisfied.
    $file = UploadedFile::fake()->create('gift.mp3', 2, 'audio/mpeg');
    file_put_contents($file->getRealPath(), random_bytes(2048));

    $test->actingAs($giver);
    $test->post(route('secret-gift.save-gift', $result->activity), [
        'gift_sound' => $file,
    ]);

    return getSecretGiftAssignmentAsGiver($result->id, $giver->id);
}

describe('SecretGift - Serve Files', function () {
    beforeEach(function () {
        Storage::fake('local');
        Storage::fake('private');
        Storage::fake('public');
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

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_sound' => UploadedFile::fake()->create('gift.mp3', 1000, 'audio/mpeg'),
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
            Storage::disk('private')->delete($assignment->gift_sound_path);

            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));
            $response->assertStatus(404);

            $response = $this->get(route('secret-gift.download-sound', [$result->activity, $assignment]));
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

        it('answers a Range request on the sound with 206 and Content-Range', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = uploadServeTestSound($this, $result, $user1);
            $size = Storage::disk('private')->size($assignment->gift_sound_path);

            $response = $this->withHeaders(['Range' => 'bytes=0-9'])
                ->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(206);
            $response->assertHeader('Content-Range', 'bytes 0-9/' . $size);
            expect($response->streamedContent())
                ->toBe(substr(Storage::disk('private')->get($assignment->gift_sound_path), 0, 10));
        });

        it('advertises byte ranges and audio/mpeg on a plain sound request', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = uploadServeTestSound($this, $result, $user1);

            $response = $this->get(route('secret-gift.sound', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Accept-Ranges', 'bytes');
            $response->assertHeader('Content-Type', 'audio/mpeg');
            expect($response->headers->get('Cache-Control'))
                ->toContain('private')
                ->toContain('max-age=3600');
            expect($response->streamedContent())
                ->toBe(Storage::disk('private')->get($assignment->gift_sound_path));
        });

        it('downloads the sound under gift-audio-{giver}-{id}.mp3', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = uploadServeTestSound($this, $result, $user1);

            $response = $this->get(route('secret-gift.download-sound', [$result->activity, $assignment]));

            $response->assertStatus(200);
            expect($response->headers->get('Content-Disposition'))
                ->toBe('attachment; filename="gift-audio-' . $user1->id . '-' . $assignment->id . '.mp3"');
            expect($response->streamedContent())
                ->toBe(Storage::disk('private')->get($assignment->gift_sound_path));
        });

        it('refuses the sound download to a non-participant', function () {
            $user1 = alice($this);
            $user2 = bob($this);
            $outsider = carol($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = uploadServeTestSound($this, $result, $user1);

            $this->actingAs($outsider);
            $response = $this->get(route('secret-gift.download-sound', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('refuses the sound download to the recipient before the end', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            uploadServeTestSound($this, $result, $user1);
            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            $this->actingAs($user2);
            $response = $this->get(route('secret-gift.download-sound', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('never exposes a gift sound under a public storage url', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $publicBefore = Storage::disk('public')->allFiles();

            $assignment = uploadServeTestSound($this, $result, $user1);

            expect(Storage::disk('public')->allFiles())->toBe($publicBefore);
            Storage::disk('public')->assertMissing($assignment->gift_sound_path);
            $this->get('/storage/' . $assignment->gift_sound_path)->assertNotFound();
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
                'gift_image' => ['file' => $file],
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
                'gift_image' => ['file' => $file],
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
                'gift_image' => ['file' => $file],
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
                'gift_image' => ['file' => $file],
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            // Outsider tries to view
            $this->actingAs($outsider);
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('streams the image bytes from the private disk', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'image/jpeg');
            expect($response->streamedContent())
                ->toBe(Storage::disk('private')->get($assignment->gift_image_path));
        });

        it('404s when the row points at a file that no longer exists', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);
            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            $assignment->gift_image_path = 'secret-gift/' . $result->id . '/gone.jpg';
            $assignment->save();

            $this->actingAs($user1);
            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(404);
        });

        it('sends a download disposition on the download route', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            $response = $this->get(route('secret-gift.download-image', [$result->activity, $assignment]));

            $response->assertStatus(200);
            expect($response->headers->get('Content-Disposition'))
                ->toContain('attachment')
                ->toContain('gift-image-' . $user1->id . '-' . $assignment->id . '.jpg');
        });

        it('answers a Range request on the image with 206 and Content-Range', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
            $size = Storage::disk('private')->size($assignment->gift_image_path);

            $response = $this->withHeaders(['Range' => 'bytes=0-9'])
                ->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(206);
            $response->assertHeader('Content-Range', 'bytes 0-9/' . $size);
            expect(strlen($response->streamedContent()))->toBe(10);
        });

        it('advertises byte ranges on a plain image request', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);

            $response = $this->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(200);
            $response->assertHeader('Accept-Ranges', 'bytes');
        });

        it('still refuses the image to a recipient before the end even with a Range header', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            $assignment = getSecretGiftAssignmentAsRecipient($result->id, $user2->id);

            $this->actingAs($user2);
            $response = $this->withHeaders(['Range' => 'bytes=0-9'])
                ->get(route('secret-gift.image', [$result->activity, $assignment]));

            $response->assertStatus(403);
        });

        it('never exposes a gift image under a public storage url', function () {
            $user1 = alice($this);
            $user2 = bob($this);

            $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

            $publicBefore = Storage::disk('public')->allFiles();

            $this->actingAs($user1);
            $this->post(route('secret-gift.save-gift', $result->activity), [
                'gift_image' => ['file' => UploadedFile::fake()->image('gift.jpg', 800, 600)],
            ]);

            expect(Storage::disk('public')->allFiles())->toBe($publicBefore);
        });
    });
});
