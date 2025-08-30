<?php

use App\Domains\Auth\Models\ActivationCode;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Helper to build a valid signed verification URL for the given user.
 */
function verificationUrlFor(User $user): string
{
    return URL::temporarySignedRoute(
        'verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );
}

it('assigns user role when activation is required and no activation code was used', function () {
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

it('assigns user-confirmed role when activation is required and an activation code was used', function () {
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

it('assigns user-confirmed role when activation is not required', function () {
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
