<?php

declare(strict_types=1);

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows the authenticated validated user name on profile page', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response->assertOk();
    $response->assertSee('Alice');
});

it('should show profile edit button if user is current user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('Edit Profile');
});