<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('redirects to intended URL after visiting login-intended and logging in', function () {
    // Create a verified user with known password via helper (logs out afterwards)
    $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

    // Ensure guest
    Auth::logout();

    // Visit the login-intended helper
    $resp = $this->get('/auth/login-intended?redirect=' . urlencode('/target-after-login'));
    $resp->assertRedirect(route('login'));

    // Login through Breeze endpoint
    $login = $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    // Should be redirected to the intended URL set by the helper
    $login->assertRedirect('/target-after-login');
});
