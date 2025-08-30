<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('shows 404 page for guests with Back, Home and Login buttons', function () {
    // Ensure we are guest
    Auth::logout();

    $resp = $this->get('/totally-unknown-page');
    $resp->assertNotFound();

    // Back button label (FR default) and Home link for guests
    $resp->assertSee(__('shared::errors.actions.back'), false);
    $resp->assertSee(__('shared::errors.actions.back_home'), false);

    // Login button is present with intended redirect
    $resp->assertSee(__('shared::errors.actions.login_to_continue'), false);
    $resp->assertSee(route('login.with_intended', ['redirect' => url('/totally-unknown-page')]), false);
});

it('shows 404 page for authenticated users with Back and Dashboard buttons', function () {
    $user = alice($this);
    $this->actingAs($user);

    $resp = $this->get('/another-unknown-page');
    $resp->assertNotFound();

    // Back button label (FR) and Dashboard CTA for auth users
    $resp->assertSee(__('shared::errors.actions.back'), false);
    $resp->assertSee(__('shared::errors.actions.go_to_dashboard'), false);

    // Should NOT show login CTA to authenticated users
    $resp->assertDontSee(__('shared::errors.actions.login_to_continue'), false);
});
