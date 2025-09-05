<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('should show only show login button to unlogged users', function () {
    $this->assertGuest();

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('shared::navigation.login');
});

it('should not show login button to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee(__('shared::navigation.login'));
});

it('should show dashboard, account, profile access and logout, but not admin to logged user', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(__('shared::navigation.dashboard'));
    $response->assertSee(__('shared::navigation.account'));
    $response->assertSee(__('shared::navigation.profile'));
    $response->assertSee(__('shared::navigation.logout'));
    $response->assertDontSee(__('shared::navigation.admin'));
});

it('should show admin link to admins', function () {
    $user = admin($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(__('shared::navigation.admin'));
});

it('should show news and stories to authenticated users', function () {
    $user = alice($this);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(__('shared::navigation.news'));
    $response->assertSee(__('shared::navigation.stories'));
});

it('should not show dashboard/admin to unverified users but show news/stories', function () {
    // Create Alice but keep her unverified
    $user = alice($this, [], false);

    $this->actingAs($user);

    $response = $this->get('/');

    $response->assertOk();
    // Visible menu entries
    $response->assertSee(__('shared::navigation.news'));
    $response->assertSee(__('shared::navigation.stories'));
    // Not visible when email not verified
    $response->assertDontSee(__('shared::navigation.dashboard'));
    $response->assertDontSee(__('shared::navigation.admin'));
});
