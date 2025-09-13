<?php

use App\Domains\Auth\Events\EmailVerified;
use App\Domains\Auth\Models\ActivationCode;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Email verification process', function () {

    it('assigns ' . Roles::USER . ' role when activation is required and no activation code was used', function () {
        // Arrange: allow registration without activation code, then require it at verification time
        config(['app.require_activation_code' => false]);

        /** @var User $user */
        $user = registerUserThroughForm($this, [
            'email' => 'need-code@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], false); // not verified yet

        // Now require activation code for verification logic
        config(['app.require_activation_code' => true]);

        $this->actingAs($user);

        // Act
        $response = $this->get(verificationUrlFor($user));

        // Assert
        $response->assertRedirect();
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeTrue();
        expect($user->isOnProbation())->toBeTrue();
        expect($user->isConfirmed())->toBeFalse();
    });

    it('assigns ' . Roles::USER_CONFIRMED . ' role when activation is required and an activation code was used', function () {
        // Arrange: allow registration without activation code, then require it at verification time
        config(['app.require_activation_code' => false]);

        /** @var User $user */
        $user = registerUserThroughForm($this, [
            'email' => 'used-code@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], false); // not verified yet

        // Simulate an activation code that has been used by this user
        ActivationCode::create([
            'code' => (string) Str::uuid(),
            'sponsor_user_id' => null,
            'used_by_user_id' => $user->id,
            'comment' => null,
            'expires_at' => null,
            'used_at' => now(),
        ]);

        // Now require activation code for verification logic
        config(['app.require_activation_code' => true]);

        $this->actingAs($user);

        // Act
        $response = $this->get(verificationUrlFor($user));

        // Assert
        $response->assertRedirect();
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeTrue();
        expect($user->isConfirmed())->toBeTrue();
        expect($user->isOnProbation())->toBeFalse();
    });

    it('assigns ' . Roles::USER_CONFIRMED . ' role when activation is not required', function () {
        // Arrange
        config(['app.require_activation_code' => false]);

        /** @var User $user */
        $user = registerUserThroughForm($this, [
            'email' => 'no-code-required@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], false); // not verified yet

        $this->actingAs($user);

        // Act
        $response = $this->get(verificationUrlFor($user));

        // Assert
        $response->assertRedirect();
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeTrue();
        expect($user->isConfirmed())->toBeTrue();
        expect($user->isOnProbation())->toBeFalse();
    });

    describe('Events', function () {
        it('emits Auth.EmailVerified with display name when user verifies email', function () {
            // Arrange
            config(['app.require_activation_code' => false]);

            /** @var User $user */
            $user = registerUserThroughForm($this, [
                'email' => 'no-code-required@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ], false); // not verified yet

            $this->actingAs($user);

            // Act
            $response = $this->get(verificationUrlFor($user));

            // Assert
            $response->assertRedirect();
            $event = latestEventOf('Auth.EmailVerified', EmailVerified::class);
            expect($event)->not->toBeNull();

            // Ensure payload contains userId and displayName (from Profile)
            /** @var EmailVerified $event */
            expect($event->userId)->toBe($user->id);

            // Summary uses translation; we just assert it contains the name and id
            expect($event->summary())
                ->toBe(__('auth::events.email_verified.summary'));
        });
    });
});
