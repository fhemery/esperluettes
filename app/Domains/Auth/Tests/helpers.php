<?php

use App\Domains\Auth\Models\User;
use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

function alice(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ], $overrides), $isVerified, $roles);
}

function bob(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Bob',
        'email' => 'bob@example.com',
    ], $overrides), $isVerified, $roles);
}

function admin(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::ADMIN, Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
    ], $overrides), $isVerified, $roles);
}

/**
 * Register a user through the real registration HTTP endpoint and return it.
 * You can override fields like email/password via $overrides.
 * If $isVerified is true (default), the user's email will be marked as verified.
 * If $ensureGuest is true (default), we'll log out any existing session before registering.
 */
function registerUserThroughForm(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER]): User
{
    $payload = array_merge([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'is_active'=>true
    ], $overrides);

    if (Auth::check()) {
        $t->post('/logout');
    }

    // We create the user through /register form, because we
    // need the profile to be created.
    $response = $t->post('/register', $payload);
    $response->assertRedirect();
    // When we create user, they get logged in automatically
    // We log them out immediately to not interfere.
    Auth::logout();


    $user = User::where('email', $payload['email'])->firstOrFail();
    if ($isVerified) {
        $user->markEmailAsVerified();
        $user->save();
    }
    foreach ($roles as $role) {
        $user->assignRole($role);
    }
    $user->refresh()->load('roles');


    return $user;
}
