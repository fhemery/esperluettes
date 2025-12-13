<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('SecretGift - Save Gift', function () {
    beforeEach(function () {
        Storage::fake('local');
    });

    it('allows a participant to save text gift', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_text' => '<p>Happy holidays! Here is my gift to you.</p>',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        expect($assignment->gift_text)->toContain('Happy holidays');
    });

    it('sanitizes HTML in gift text using strict purifier', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_text' => '<p>Hello</p><script>alert("xss")</script><img src="x" onerror="alert(1)">',
        ]);

        $response->assertRedirect();

        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        expect($assignment->gift_text)->not->toContain('<script>');
        expect($assignment->gift_text)->not->toContain('onerror');
        expect($assignment->gift_text)->toContain('Hello');
    });

    it('allows a participant to upload an image gift', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $file = UploadedFile::fake()->image('gift.jpg', 800, 600);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        expect($assignment->gift_image_path)->not->toBeNull();
        Storage::disk('local')->assertExists($assignment->gift_image_path);
    });

    it('rejects non-participant from saving gift', function () {
        $user1 = alice($this);
        $user2 = bob($this);
        $outsider = carol($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($outsider);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_text' => '<p>Sneaky gift</p>',
        ]);

        $response->assertStatus(403);
    });

    it('rejects saving gift when activity is not active', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createEndedSecretGift($this);
        registerSecretGiftParticipants($result->id, [$user1->id, $user2->id]);
        shuffleSecretGift($result->activity);

        $this->actingAs($user1);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_text' => '<p>Too late!</p>',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('validates image file type', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $file = UploadedFile::fake()->create('gift.pdf', 100, 'application/pdf');

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_image' => $file,
        ]);

        $response->assertSessionHasErrors('gift_image');
    });

    it('validates image file size', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $file = UploadedFile::fake()->image('gift.jpg')->size(6000); // 6MB > 5MB limit

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_image' => $file,
        ]);

        $response->assertSessionHasErrors('gift_image');
    });

    it('allows saving both text and image together', function () {
        $user1 = alice($this);
        $user2 = bob($this);

        $result = createShuffledSecretGift($this, [$user1->id, $user2->id]);

        $this->actingAs($user1);

        $file = UploadedFile::fake()->image('gift.png', 400, 400);

        $response = $this->post(route('secret-gift.save-gift', $result->activity), [
            'gift_text' => '<p>A poem for you!</p>',
            'gift_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment = getSecretGiftAssignmentAsGiver($result->id, $user1->id);
        expect($assignment->gift_text)->toContain('poem');
        expect($assignment->gift_image_path)->not->toBeNull();
    });
});
