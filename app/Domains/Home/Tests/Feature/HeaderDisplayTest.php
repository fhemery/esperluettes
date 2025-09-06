<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('guest header display', function () {
    it('should have logo link point to home', function () {
        $this->assertGuest();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeInOrder(['<a href="' . route('home'), 'id="header-logo"'], false);
    });

    it('should show only show login button, news and stories to unlogged users', function () {
        $this->assertGuest();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('shared::navigation.login'));
        $response->assertSee(__('shared::navigation.news'));
        $response->assertSee(__('shared::navigation.stories'));
    });

    it('should not show login button when on login page', function () {
        $this->assertGuest();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee(__('shared::navigation.login'));
    });
});

describe('Logged header display', function () {
    it('should have logo link point to dashboard', function () {
        $user = alice($this);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder(['<a href="' . route('dashboard'), 'id="header-logo"'], false);
    });

    it('should not show login button to logged user', function () {
        $user = alice($this);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(__('shared::navigation.login'));
    });

    it('should show dashboard, account, profile access and logout, but not admin to logged user', function () {
        $user = alice($this);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

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

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('shared::navigation.admin'));
    });

    it('should show news and stories to authenticated users', function () {
        $user = alice($this);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('shared::navigation.news'));
        $response->assertSee(__('shared::navigation.stories'));
    });

    it('should not show dashboard/admin to unverified users but show news/stories', function () {
        // Create Alice but keep her unverified
        $user = alice($this, [], false);

        $this->actingAs($user);

        $response = $this->get(route('verification.notice'));

        $response->assertOk();
        // Visible menu entries
        $response->assertSee(__('shared::navigation.news'));
        $response->assertSee(__('shared::navigation.stories'));
        // Not visible when email not verified
        $response->assertDontSee(__('shared::navigation.dashboard'));
        $response->assertDontSee(__('shared::navigation.admin'));
    });
});
