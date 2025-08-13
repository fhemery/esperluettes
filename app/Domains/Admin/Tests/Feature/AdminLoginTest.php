<?php

declare(strict_types=1);

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows an admin user to access the Filament panel', function () {
    $admin = User::factory()->create([ 'is_active' => true ]);
    $admin->assignRole('admin');

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
    $user = User::factory()->create([ 'is_active' => true ]);
    $user->assignRole('user');

    $this->actingAs($user);

    $response = $this->get('/admin');

    $response->assertRedirect('/dashboard');
});