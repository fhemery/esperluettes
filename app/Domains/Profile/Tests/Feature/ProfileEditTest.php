<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Public\Events\AvatarChanged;
use App\Domains\Profile\Public\Events\ProfileDisplayNameChanged;
use App\Domains\Profile\Public\Events\BioUpdated;

use App\Domains\Profile\Private\Api\ProfileApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Editing profile', function () {

    it('redirects guests from profile edit route to login', function () {
        $this->get('/profile/edit')->assertRedirect('/login');
    });

    describe('Page access', function () {

        it('allows authenticated user with proper role to access edit page', function () {
            $userConfirmed = alice($this);
            $simpleUser = bob($this, roles: [Roles::USER]);

            $this->actingAs($simpleUser)
                ->get('/profile/edit')
                ->assertOk()
                ->assertSee('Bob');
            $this->actingAs($userConfirmed)
                ->get('/profile/edit')
                ->assertOk()
                ->assertSee('Alice');
        });
    });

    describe('Name update', function () {
        it('updates display_name and slug when editing the display name', function () {
            // Arrange: register user through real endpoint and verify
            $user = registerUserThroughForm($this, [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
    
            // Act: authenticated + verified user updates display name
            $this->actingAs($user)
                ->put('/profile', [
                    'display_name' => 'Johnny Bravo',
                ])
                ->assertRedirect();
    
            // Assert: profile updated with new display name and new slug
            $updated = app(ProfileApi::class)->getPublicProfile($user->id);
            expect($updated->display_name)->toBe('Johnny Bravo');
            expect($updated->slug)->toBe('johnny-bravo');
        });

        it('rejects duplicate display name on profile update', function () {
            // Register two users via real flow
            $userA = registerUserThroughForm($this, [
                'name' => 'Alice Unique',
                'email' => 'alice.unique@example.com',
            ]);
            $userB = registerUserThroughForm($this, [
                'name' => 'Bob Starter',
                'email' => 'bob.starter@example.com',
            ]);

            // Bob tries to change display name to Alice's
            $response = $this->actingAs($userB)
                ->from('/profile/edit')
                ->put('/profile', [
                    'display_name' => 'Alice Unique',
                ]);

            $response->assertRedirect('/profile/edit');
            $response->assertSessionHasErrors(['display_name']);

            // Act: Bob attempts to change display_name to a value that normalizes to same slug
            $response = $this->actingAs($userB)
                ->from('/profile/edit')
                ->put('/profile', [
                    'display_name' => 'alice !unIquE',
                ]);

            // Assert: validation error and redirect back
            $response->assertRedirect('/profile/edit');
            $response->assertSessionHasErrors(['display_name']);
        });

        it('allows keeping the same display name (self-update)', function () {
            // Arrange: register a user and capture current display_name
            $user = registerUserThroughForm($this, [
                'name' => 'Same Name',
                'email' => 'same.name@example.com',
            ]);

            // Act: user submits the same display_name
            $response = $this->actingAs($user)
                ->from('/profile')
                ->put('/profile', [
                    'display_name' => 'Same Name',
                ]);

            // Assert: success redirect, no validation errors, name unchanged
            $response->assertRedirect('/profile/edit');
            $response->assertSessionHasNoErrors();
        });
    });

    describe('Events', function () {
        beforeEach(function () {
            Storage::fake('public');
        });
    
        it('dispatches ProfileDisplayNameChanged event when updating the display name', function () {
            // Arrange: register user and verify
            $user = registerUserThroughForm($this, [
                'name' => 'John Doe',
                'email' => 'john2@example.com',
            ]);

            // Act
            $this->actingAs($user)
                ->put('/profile', [
                    'display_name' => 'Johnny Bravo',
                ])
                ->assertRedirect();

            // Assert
            $event = latestEventOf(ProfileDisplayNameChanged::name(), ProfileDisplayNameChanged::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->oldDisplayName)->toBe('John Doe');
            expect($event->newDisplayName)->toBe('Johnny Bravo');
        });

        it('emits AvatarChanged event when uploading a new avatar', function () {
            $user = alice($this);
            $this->actingAs($user);
    
            $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
    
            $response = $this->from('/profile/edit')->put('/profile', [
                'profile_picture' => $file,
            ]);
            $response->assertRedirect('/profile/edit');
    
            /** @var AvatarChanged $event */
            $event = latestEventOf(AvatarChanged::name(), AvatarChanged::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->profilePicturePath)->not->toBeNull();
        });
    
        it('emits AvatarChanged event when removing the avatar', function () {
            $user = alice($this);
            $this->actingAs($user);
    
            // First upload to ensure there is an avatar to remove
            $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
            $this->put('/profile', ['profile_picture' => $file])->assertRedirect('/profile/edit');
    
            // Then remove
            $response = $this->from('/profile/edit')->put('/profile', [
                'remove_profile_picture' => true,
            ]);
            $response->assertRedirect('/profile/edit');
    
            /** @var AvatarChanged $event */
            $event = latestEventOf(AvatarChanged::name(), AvatarChanged::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->profilePicturePath)->toBeNull();
        });

        it('emits BioUpdated when updating the bio/description', function () {
            $user = alice($this);
            $this->actingAs($user);

            // Update only the description
            $response = $this->from('/profile/edit')->put('/profile', [
                'description' => '<b>Hello</b> world',
            ]);
            $response->assertRedirect('/profile/edit');

            /** @var BioUpdated $event */
            $event = latestEventOf(BioUpdated::name(), BioUpdated::class);
            expect($event)->not->toBeNull();
            // sanitized content expected; the purifier wraps plain text in a <p>
            expect($event->description)->toBe('<p>Hello world</p>');
            expect($event->userId)->toBe($user->id);
        });

        it('emits BioUpdated when updating social network links', function () {
            $user = alice($this);
            $this->actingAs($user);

            $response = $this->from('/profile/edit')->put('/profile', [
                'facebook_url' => 'facebook.com/someone',
                'x_url' => 'twitter.com/someone',
                'instagram_url' => 'https://instagram.com/someone',
                'youtube_url' => 'youtu.be/abc123',
            ]);
            $response->assertRedirect('/profile/edit');

            /** @var BioUpdated $event */
            $event = latestEventOf(BioUpdated::name(), BioUpdated::class);
            expect($event)->not->toBeNull();
            expect($event->facebookUrl)->toContain('https://');
            expect($event->xUrl)->toContain('https://');
            expect($event->instagramUrl)->toContain('https://');
            expect($event->youtubeUrl)->toContain('https://');
            expect($event->userId)->toBe($user->id);
        });
    });

    describe('SEO', function () {
        it('should have the correct title', function () {
            $user = alice($this);

            $this->actingAs($user)->get('/profile/edit')
                ->assertSee(__('profile::edit.title', ['name' => 'Alice']));
        });
    });
});
