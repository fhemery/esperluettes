<?php

use App\Domains\Auth\Events\UserDeactivated;
use App\Domains\Auth\Events\UserReactivated;
use App\Domains\Auth\Services\UserActivationService;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Auth user activation and deactivation', function () {
    describe('Functional', function () {
        it('prevents login for a deactivated user', function () {
            // Create a normal user with known credentials from helper
            $user = alice($this); // default password: 'secret-password'
            expect($user->isActive())->toBeTrue();

            // Admin deactivates the user
            $admin = admin($this);
            $this->actingAs($admin);
            app(UserActivationService::class)->deactivateUser($user);

            // Ensure logout to simulate fresh login attempt
            $this->post('/logout');

            // Attempt login as deactivated user
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertGuest();
        });

        it('allows login again after reactivation', function () {
            $user = alice($this);
            $admin = admin($this);
            $this->actingAs($admin);

            // Deactivate then reactivate
            app(UserActivationService::class)->deactivateUser($user);
            app(UserActivationService::class)->activateUser($user);

            // Logout admin to simulate user login
            $this->post('/logout');

            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'secret-password',
            ]);

            // Should redirect to dashboard as per AuthenticatedSessionController
            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticated();
        });
    });

    describe('Events', function () {

        it('emits UserDeactivated when deactivating an active user', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = alice($this); // verified and confirmed by default
            expect($target->isActive())->toBeTrue();

            /** @var UserActivationService $svc */
            $svc = app(UserActivationService::class);
            $svc->deactivateUser($target);

            /** @var UserDeactivated|null $event */
            $event = latestEventOf(UserDeactivated::name(), UserDeactivated::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($target->id);
        });

        it('emits UserReactivated when activating an inactive user', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = alice($this);
            // First deactivate
            app(UserActivationService::class)->deactivateUser($target);
            $target->refresh();
            expect($target->isActive())->toBeFalse();

            // Then activate
            app(UserActivationService::class)->activateUser($target);

            /** @var UserReactivated|null $event */
            $event = latestEventOf(UserReactivated::name(), UserReactivated::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($target->id);
        });

        it('does not emit when deactivating an already inactive user (no-op)', function () {
            /** @var User $admin */
            $admin = admin($this);
            $this->actingAs($admin);

            /** @var User $target */
            $target = alice($this);
            app(UserActivationService::class)->deactivateUser($target);
            app(UserActivationService::class)->deactivateUser($target);

            expect(countEvents(UserDeactivated::name()))->toBe(1);
        });
    });

    
});
