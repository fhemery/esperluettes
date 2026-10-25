<?php

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Models\Profile;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Soft deleting a profile after user deactivation', function () {

    it('soft-deletes the profile on user deactivation and hides it from public API', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        // Sanity: profile exists publicly before deactivation
        /** @var ProfilePublicApi $publicApi */
        $publicApi = app(ProfilePublicApi::class);
        $this->assertNotNull($publicApi->getPublicProfile($user->id));

        // Act: deactivate via AuthPublicApi (emits UserDeactivated)
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);

        // Assert: public API no longer returns profile
        $this->assertNull($publicApi->getPublicProfile($user->id));

        // Assert: profile row still exists but is soft-deleted
        /** @var Profile|null $p */
        $p = Profile::withTrashed()->where('user_id', $user->id)->first();
        expect($p)->not->toBeNull();
        expect($p->trashed())->toBeTrue();
    });
});

describe('Opening the profile page of a deactivated user', function () {

    it('answers 404, never 500, to guests and logged-in users, on the default and a named tab', function () {
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);
        $viewer = bob($this, roles: [Roles::USER_CONFIRMED]);
        $slug = Profile::where('user_id', $user->id)->firstOrFail()->slug;

        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);
        auth()->logout();

        $this->get("/profile/{$slug}")->assertNotFound();
        $this->get("/profile/{$slug}/stories")->assertNotFound();
        $this->actingAs($viewer)->get("/profile/{$slug}")->assertNotFound();
        $this->actingAs($admin)->get("/profile/{$slug}")->assertNotFound();
    });
});
