<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Public\Events\AvatarModerated;
use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Profile moderation', function () {
    beforeEach(function () {
        // Ensure we have a target profile to moderate
        $this->owner = alice($this);
        $this->profile = Profile::where('user_id', $this->owner->id)->firstOrFail();
        $this->slug = $this->profile->slug;
        $this->targetUrl = "/profile/{$this->slug}/moderation/remove-image";
        $this->referer = "/profile/{$this->slug}";
    });

    describe('Access', function () {
        it('redirects guests to login when accessing moderation route', function () {
            $this->post($this->targetUrl)
                ->assertRedirect('/login');
        });

        it('denies access to non-moderators (user, user-confirmed) by redirecting to dashboard', function () {
            $confirmed = bob($this);
            $this->actingAs($confirmed)
                ->post($this->targetUrl)
                ->assertRedirect(route('dashboard'));
        });

        it('allows moderator to access and redirects back with success message', function () {
            $moderatorUser = moderator($this);

            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);
        });
    });

    describe('Remove image', function () {
        it('removes custom profile picture from storage and database', function () {
            // Upload a custom avatar for Alice
            $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 300, 300);
            $this->actingAs($this->owner)
                ->put('/profile', ['profile_picture' => $file])
                ->assertRedirect('/profile');

            // Verify the picture was uploaded
            $this->profile->refresh();
            expect($this->profile->profile_picture_path)->not->toBeNull();
            $picturePath = $this->profile->profile_picture_path;
            expect(Storage::disk('public')->exists($picturePath))->toBeTrue();

            // Moderator removes the image
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer)
                ->assertSessionHas('success', __('profile::moderation.remove_image.success'));

            // Verify picture is deleted from storage
            expect(Storage::disk('public')->exists($picturePath))->toBeFalse();

            // Verify database field is null
            $this->profile->refresh();
            expect($this->profile->profile_picture_path)->toBeNull();
        });

        it('does not display custom image on profile page after removal', function () {
            // Upload a custom avatar for Alice
            $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 300, 300);
            $this->actingAs($this->owner)
                ->put('/profile', ['profile_picture' => $file])
                ->assertRedirect('/profile');

            $this->profile->refresh();
            $picturePath = $this->profile->profile_picture_path;
            $publicUrl = Storage::disk('public')->url($picturePath);

            // Verify custom image is shown before removal
            $response = $this->get("/profile/{$this->slug}");
            $response->assertSee($publicUrl, false);

            // Moderator removes the image
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            // Verify custom image is NOT shown after removal
            $response = $this->get("/profile/{$this->slug}");
            $response->assertDontSee($publicUrl, false);
        });

        it('does nothing when there is no custom image', function () {
            // Arrange: simulate no custom image on profile
            $this->profile->update(['profile_picture_path' => null]);

            // Default avatar path created at registration
            $defaultPath = 'profile_pictures/' . $this->owner->id . '.svg';
            expect(Storage::disk('public')->exists($defaultPath))->toBeTrue();

            // Act: moderator triggers removal
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            // Assert: DB remains null, default avatar still present
            $this->profile->refresh();
            expect($this->profile->profile_picture_path)->toBeNull();
            expect(Storage::disk('public')->exists($defaultPath))->toBeTrue();
        });

        it('emits AvatarModerated event when moderator removes the image', function () {
            // Upload a custom avatar for Alice first
            $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 300, 300);
            $this->actingAs($this->owner)
                ->put('/profile', ['profile_picture' => $file])
                ->assertRedirect('/profile');

            $this->profile->refresh();
            expect($this->profile->profile_picture_path)->not->toBeNull();

            // Moderator removes the image
            $moderatorUser = moderator($this);
            $this->from($this->referer)
                ->actingAs($moderatorUser)
                ->post($this->targetUrl)
                ->assertRedirect($this->referer);

            /** @var AvatarModerated $event */
            $event = latestEventOf(AvatarModerated::name(), AvatarModerated::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($this->owner->id);
            expect($event->profilePicturePath)->toBeNull();
        });
    });
});
