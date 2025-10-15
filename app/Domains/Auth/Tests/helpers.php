<?php

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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

function carol(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Carol',
        'email' => 'carol@example.com',
    ], $overrides), $isVerified, $roles);
}

function admin(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::ADMIN, Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Admin',
        'email' => 'admin@admin.com',
    ], $overrides), $isVerified, $roles);
}

function techAdmin(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::TECH_ADMIN, Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Tech Admin',
        'email' => 'techadmin@techadmin.com',
    ], $overrides), $isVerified, $roles);
}

function moderator(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::MODERATOR, Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Moderator',
        'email' => 'moderator@moderator.com',
    ], $overrides), $isVerified, $roles);
}

/**
 * Helper to build a valid signed verification URL for the given user.
 */
function verificationUrlFor(User $user): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );
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

/**
 * Delete a user in tests
 */
function deleteUser(TestCase $t, User $user, string $password = 'secret-password'): void
{
    // Perform the real flow through the controller so domain events and guards apply
    $user->refresh();
    $t->actingAs($user);

    $response = $t->delete(route('account.destroy'), [
        'password' => $password,
    ]);

    $response->assertRedirect('/');
}
