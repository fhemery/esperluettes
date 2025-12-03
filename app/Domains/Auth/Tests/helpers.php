<?php

use App\Domains\Auth\Private\Models\ActivationCode;
use App\Domains\Auth\Private\Models\PromotionRequest;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Services\PromotionRequestService;
use App\Domains\Auth\Private\Services\UserService;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Set whether activation code is required for registration.
 * Convenience wrapper around setParameterValue for Auth domain.
 */
function setActivationCodeRequired(bool $required): void
{
    setParameterValue(AuthConfigKeys::REQUIRE_ACTIVATION_CODE, AuthConfigKeys::DOMAIN, $required);
}

/**
 * Create an activation code for testing.
 *
 * @param int|null $sponsorUserId The sponsor user ID (optional)
 * @param int|null $usedByUserId If set, marks the code as used by this user
 * @param Carbon|null $expiresAt Expiration date (default: 30 days from now)
 * @return ActivationCode
 */
function createActivationCode(?int $sponsorUserId = null, ?int $usedByUserId = null, ?Carbon $expiresAt = null): ActivationCode
{
    $code = ActivationCode::create([
        'code' => strtoupper(Str::random(4) . '-' . Str::random(8) . '-' . Str::random(4)),
        'sponsor_user_id' => $sponsorUserId,
        'expires_at' => $expiresAt ?? now()->addDays(30),
    ]);

    if ($usedByUserId !== null) {
        $code->update([
            'used_by_user_id' => $usedByUserId,
            'used_at' => now(),
        ]);
    }

    return $code;
}

/**
 * Create a pending promotion request for a user.
 */
function createPromotionRequest(User $user, int $commentCount = 10, ?Carbon $requestedAt = null): PromotionRequest
{
    return PromotionRequest::create([
        'user_id' => $user->id,
        'status' => PromotionRequest::STATUS_PENDING,
        'comment_count' => $commentCount,
        'requested_at' => $requestedAt ?? now(),
    ]);
}

/**
 * Accept a promotion request.
 */
function acceptPromotionRequest(PromotionRequest $request, User $decidedBy): bool
{
    return app(PromotionRequestService::class)->acceptRequest($request->id, $decidedBy->id);
}

/**
 * Reject a promotion request.
 */
function rejectPromotionRequest(PromotionRequest $request, User $decidedBy, string $reason = 'Rejected'): bool
{
    return app(PromotionRequestService::class)->rejectRequest($request->id, $decidedBy->id, $reason);
}

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

function daniel(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER_CONFIRMED]): User
{
    return registerUserThroughForm($t, array_merge([
        'name' => 'Daniel',
        'email' => 'daniel@example.com',
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
function registerUserThroughForm(TestCase $t, array $overrides = [], bool $isVerified = true, array $roles = [Roles::USER], bool $activationCodeRequired = false): User
{
    setActivationCodeRequired($activationCodeRequired);
    $payload = array_merge([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'is_active'=>true,
        'accept_terms'=>true
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

/**
 * Deactivate a user in tests.
 * 
 * It does go through the service, which means it sends all the needed events
 */
function deactivateUser(User $user): void
{
    $service = app(UserService::class);
    $service->deactivateUser($user);
}
