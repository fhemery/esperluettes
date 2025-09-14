<?php

use App\Domains\Auth\Events\UserRoleGranted;
use App\Domains\Auth\Events\UserRoleRevoked;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Auth\Services\RoleService;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Auth role assignment', function () {
    describe('Events', function () {
        it('emits UserRoleGranted when an admin grants a role to another user', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = bob($this, roles: [Roles::USER]); // ensure not confirmed initially
            expect($target->hasRole(Roles::USER_CONFIRMED))->toBeFalse();

            /** @var RoleService $svc */
            $svc = app(RoleService::class);
            $svc->grant($target, Roles::USER_CONFIRMED);

            /** @var UserRoleGranted|null $event */
            $event = latestEventOf(UserRoleGranted::name(), UserRoleGranted::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($target->id);
            expect($event->role)->toBe(Roles::USER_CONFIRMED);
            // actor is different from target, should use normal summary translation key (avoid locale dependencies)
            expect($event->summary())->not->toContain('system');
        });

        it('emits system summary when a non-admin user (self-actor) gets a role', function () {
            /** @var User $user */
            $user = admin($this, roles: [Roles::USER]);
            // self-actor: act as the same user
            $this->actingAs($user);

            /** @var RoleService $svc */
            $svc = app(RoleService::class);
            $svc->grant($user, Roles::USER_CONFIRMED);

            /** @var UserRoleGranted|null $event */
            $event = latestEventOf(UserRoleGranted::name(), UserRoleGranted::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
            expect($event->role)->toBe(Roles::USER_CONFIRMED);
            expect($event->summary())->toContain('system');
        });

        it('does not emit when granting an already granted role (no-op)', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = bob($this);
            $target->assignRole(Roles::USER_CONFIRMED); // pre-existing

            /** @var RoleService $svc */
            $svc = app(RoleService::class);
            $svc->grant($target, Roles::USER_CONFIRMED); // no-op

            /** @var UserRoleGranted|null $event */
            $event = latestEventOf(UserRoleGranted::name(), UserRoleGranted::class);
            // Should remain null because nothing was emitted in this test
            expect($event)->toBeNull();
        });

        it('emits UserRoleRevoked when an admin revokes a role from a user', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = bob($this);
            $target->assignRole(Roles::USER_CONFIRMED);

            /** @var RoleService $svc */
            $svc = app(RoleService::class);
            $svc->revoke($target, Roles::USER_CONFIRMED);

            /** @var UserRoleRevoked|null $event */
            $event = latestEventOf(UserRoleRevoked::name(), UserRoleRevoked::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($target->id);
            expect($event->role)->toBe(Roles::USER_CONFIRMED);
            expect($event->summary())->not->toContain('system');
        });
    });
});
