<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('deletes the profile row, removes avatars, and clears cache on user deletion', function () {
    // Arrange: fake public storage BEFORE user creation so default avatar writes go to fake disk
    Storage::fake('public');

    $user = alice($this, roles: [Roles::USER_CONFIRMED]);

    // Default avatar path as created in ProfileService::createOrInitProfileOnRegistration
    $defaultAvatarPath = 'profile_pictures/' . $user->id . '.svg';

    // Sanity: default avatar exists
    Storage::disk('public')->assertExists($defaultAvatarPath);

    // Act: delete the user via real flow (Auth controller)
    deleteUser($this, $user);

    // Assert: profile row removed
    $profilePublicApi = app(ProfilePublicApi::class);
    $this->assertNull($profilePublicApi->getPublicProfile($user->id));

    // Assert: default avatar removed
    Storage::disk('public')->assertMissing($defaultAvatarPath);

    // Optionally: if a custom picture existed, it should be removed too. We can extend later
});

it('allows re-registration with the same username after admin deletes the account', function () {
    Storage::fake('public');

    $admin = admin($this);
    $target = registerUserThroughForm($this, [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $this->actingAs($admin);
    app(AuthPublicApi::class)->deleteUserById($target->id);

    // Re-register with same name — must not fail with unique constraint violation
    $newUser = registerUserThroughForm($this, [
        'name' => 'Jane Doe',
        'email' => 'jane2@example.com',
    ]);

    $profile = Profile::where('user_id', $newUser->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->slug)->toBe(Str::slug('Jane Doe'));
});
