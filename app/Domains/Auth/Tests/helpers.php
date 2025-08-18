<?php

use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

function alice(TestCase $t, array $overrides = [], bool $isVerified = true): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ], $overrides), $isVerified);
}

function bob(TestCase $t, array $overrides = [], bool $isVerified = true): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Bob',
        'email' => 'bob@example.com',
    ], $overrides), $isVerified);
}

/**
 * Register a user through the real registration HTTP endpoint and return it.
 * You can override fields like email/password via $overrides.
 * If $isVerified is true (default), the user's email will be marked as verified.
 * If $ensureGuest is true (default), we'll log out any existing session before registering.
 */
function registerUserThroughForm(TestCase $t, array $overrides = [], bool $isVerified = true, bool $ensureGuest = true): User
{
    $payload = array_merge([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ], $overrides);

    if ($ensureGuest && Auth::check()) {
        $t->post('/logout');
    }

    $response = $t->post('/register', $payload);
    $response->assertRedirect();

    $user = User::where('email', $payload['email'])->firstOrFail();
    if ($isVerified) {
        $user->forceFill(['email_verified_at' => now()])->save();
    }

    return $user;
}
