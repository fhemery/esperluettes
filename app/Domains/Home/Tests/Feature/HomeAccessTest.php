<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects authenticated users from home to dashboard', function () {
    $user = alice($this);
    $this->actingAs($user);

    $this->get('/')
        ->assertRedirect(route('dashboard'));
});

it('shows the guest home page with expected test id', function () {
    $this->assertGuest();

    $response = $this->get('/');

    $response->assertOk();
    // Ensure we render the home page container with the data-test-id attribute
    $response->assertSee('data-test-id="home-page"', false);
});
