<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows an admin user to access the Filament panel', function () {
    $admin = admin($this);

    $this->actingAs($admin);

    $response = $this->get('/admin');

    if ($response->isRedirect()) {
        $response = $this->followRedirects($response);
    }

    $response->assertOk();
});

it('redirects guests trying to access the Filament panel', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/login');
});

it('redirects non admins to dashboard', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/admin');

    $response->assertRedirect('/dashboard');
});