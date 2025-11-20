<?php

use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Auth admin user activation endpoints', function () {
    it('deactivates an active user and returns 204', function () {
        $admin = admin($this);
        $target = alice($this);
        expect($target->isActive())->toBeTrue();

        $this->actingAs($admin);

        $response = $this->post(route('auth.admin.users.deactivate', $target->id));

        $response->assertNoContent();

        /** @var User $reloaded */
        $reloaded = User::query()->findOrFail($target->id);
        expect($reloaded->isActive())->toBeFalse();
    });

    it('reactivates an inactive user and returns 204', function () {
        $admin = admin($this);
        $target = alice($this);

        $this->actingAs($admin);
        // First deactivate through the endpoint
        $this->post(route('auth.admin.users.deactivate', $target->id))->assertNoContent();

        /** @var User $deactivated */
        $deactivated = User::query()->findOrFail($target->id);
        expect($deactivated->isActive())->toBeFalse();

        // Then reactivate
        $response = $this->post(route('auth.admin.users.reactivate', $target->id));

        $response->assertNoContent();

        /** @var User $reloaded */
        $reloaded = User::query()->findOrFail($target->id);
        expect($reloaded->isActive())->toBeTrue();
    });

    it('is idempotent when deactivating an already inactive user', function () {
        $admin = admin($this);
        $target = alice($this);

        $this->actingAs($admin);

        // First call
        $this->post(route('auth.admin.users.deactivate', $target->id))->assertNoContent();
        // Second call should still be 204 and user remains inactive
        $this->post(route('auth.admin.users.deactivate', $target->id))->assertNoContent();

        /** @var User $reloaded */
        $reloaded = User::query()->findOrFail($target->id);
        expect($reloaded->isActive())->toBeFalse();
    });

    it('is idempotent when reactivating an already active user', function () {
        $admin = admin($this);
        $target = alice($this);
        expect($target->isActive())->toBeTrue();

        $this->actingAs($admin);

        // Reactivate twice should both return 204 and keep user active
        $this->post(route('auth.admin.users.reactivate', $target->id))->assertNoContent();
        $this->post(route('auth.admin.users.reactivate', $target->id))->assertNoContent();

        /** @var User $reloaded */
        $reloaded = User::query()->findOrFail($target->id);
        expect($reloaded->isActive())->toBeTrue();
    });

    it('returns 401 and redirects to login when not authenticated', function () {
        $target = alice($this);

        $response = $this->post(route('auth.admin.users.deactivate', $target->id));

        $response->assertRedirect(route('login'));
    });

    it('returns 403 when authenticated user does not have required role', function () {
        $normalUser = alice($this);
        $target = bob($this);

        $this->actingAs($normalUser);

        $response = $this->post(route('auth.admin.users.deactivate', $target->id));

        $response->assertRedirect(route('dashboard'));
    });

    it('returns 404 when target user does not exist (deactivate)', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $nonExistentUserId = 9_999_999;
        $response = $this->post(route('auth.admin.users.deactivate', $nonExistentUserId));

        $response->assertNotFound();
    });

    it('returns 404 when target user does not exist (reactivate)', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $nonExistentUserId = 9_999_999;
        $response = $this->post(route('auth.admin.users.reactivate', $nonExistentUserId));

        $response->assertNotFound();
    });
});
