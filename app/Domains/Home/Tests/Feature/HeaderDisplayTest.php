<?php

declare(strict_types=1);

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('should show the register and login button to unlogged users', function () {
    $this->assertGuest();

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Log in');
    $response->assertSee('Register');
});

it('should not show the register and login button to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $this->assertStringNotContainsString('Log in', $response->content());
    $this->assertStringNotContainsString('Register', $response->content());
});

it('should show dashboard, account, profile access and logout, but not admin to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertSee('Account');
    $response->assertSee('Profile');
    $response->assertSee('Log Out');
    $response->assertDontSee('Admin');
});