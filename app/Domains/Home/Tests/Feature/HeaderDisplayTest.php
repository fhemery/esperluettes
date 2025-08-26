<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('should show the register and login button to unlogged users', function () {
    $this->assertGuest();

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('shared::navigation.login');
    $response->assertSee('shared::navigation.register');
});

it('should not show the register and login button to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $this->assertStringNotContainsString('shared::navigation.login', $response->content());
    $this->assertStringNotContainsString('shared::navigation.register', $response->content());
});

it('should show dashboard, account, profile access and logout, but not admin to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('shared::navigation.dashboard');
    $response->assertSee('shared::navigation.account');
    $response->assertSee('shared::navigation.profile');
    $response->assertSee('shared::navigation.logout');
    $response->assertDontSee('shared::navigation.admin');
});

it('should show admin link to admins', function () {
    $user = admin($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('shared::navigation.admin');
});

it('should show news and stories to authenticated users', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('shared::navigation.news');
    $response->assertSee('shared::navigation.stories');
});

it('should not show dashboard/admin to unverified users but show news/stories', function () {
    // Create Alice but keep her unverified
    $user = alice($this, [], false);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    // Visible menu entries
    $response->assertSee('shared::navigation.news');
    $response->assertSee('shared::navigation.stories');
    // Not visible when email not verified
    $response->assertDontSee('shared::navigation.dashboard');
    $response->assertDontSee('shared::navigation.admin');
});
