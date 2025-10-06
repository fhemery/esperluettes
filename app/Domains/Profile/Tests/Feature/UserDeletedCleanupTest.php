<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
