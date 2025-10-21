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

describe('Restoring a profile after user reactivation', function () {

    it('restores the soft-deleted profile on user reactivation and makes it visible again', function () {
        // Arrange
        $admin = admin($this);
        $user = alice($this, roles: [Roles::USER_CONFIRMED]);

        /** @var ProfilePublicApi $publicApi */
        $publicApi = app(ProfilePublicApi::class);
        $this->assertNotNull($publicApi->getPublicProfile($user->id));

        // First deactivate (soft delete profile)
        $this->actingAs($admin);
        app(AuthPublicApi::class)->deactivateUserById($user->id);
        $this->assertNull($publicApi->getPublicProfile($user->id));
        $p = Profile::withTrashed()->where('user_id', $user->id)->first();
        expect($p)->not->toBeNull();
        expect($p->trashed())->toBeTrue();

        // Act: reactivate via AuthPublicApi
        app(AuthPublicApi::class)->activateUserById($user->id);

        // Assert: public profile is visible again
        $this->assertNotNull($publicApi->getPublicProfile($user->id));

        // Assert: profile restored (not trashed)
        $p = Profile::withTrashed()->where('user_id', $user->id)->first();
        expect($p)->not->toBeNull();
        expect($p->trashed())->toBeFalse();
    });
});
