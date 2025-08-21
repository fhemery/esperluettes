<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('redirects guests to login when accessing /stories/create', function () {
    $response = $this->get('/stories/create');

    $response->assertRedirect('/login');
});

it('allows user-confirmed users to access the story creation page', function () {
    $user = alice($this, roles: ['user-confirmed']);

    $response = $this->actingAs($user)->get('/stories/create');

    $response->assertOk();
    $response->assertSee(__('story::create.title'));
});

it('denies users with only the user role from accessing the story creation page', function () {
    $user = alice($this, roles: ['user']);

    $response = $this->actingAs($user)->get('/stories/create');

    // Our CheckRole middleware redirects unauthorized roles to dashboard
    $response->assertRedirect(route('dashboard'));
});
