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
    $user = alice($this, [], false);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('verification.notice'));
});

it('allows verified users to access dashboard', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertSee("You're logged in!");
});
