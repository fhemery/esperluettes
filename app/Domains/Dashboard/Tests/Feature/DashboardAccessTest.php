<?php

declare(strict_types=1);

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests to login when accessing dashboard', function () {
    $this->assertGuest();

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

it('redirects unverified users to verification notice', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('verification.notice'));
});

it('allows verified users to access dashboard', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertSee("You're logged in!");
});
