<?php

declare(strict_types=1);

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows the authenticated validated user name on profile page', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/profile'); // uses ProfileController@showOwn

    $response->assertOk();
    $response->assertSee('John Doe');
});

it('should show profile edit button if user is current user', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response->assertOk();
    $response->assertSee('John Doe');
    $response->assertSee('Edit Profile');
});