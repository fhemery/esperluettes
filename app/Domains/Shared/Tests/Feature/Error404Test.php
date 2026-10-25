<?php

use App\Domains\Shared\Contracts\Theme;
use App\Domains\Shared\Providers\SharedServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
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

it('shows the 404 page to a guest on a missing model-bound profile', function () {
    $resp = $this->get('/profile/nobody-xx');

    $resp->assertNotFound();
    $resp->assertSee(__('shared::errors.actions.back_home'), false);
    $resp->assertSee('images/themes/'.Theme::seasonal()->value.'/errors/404.png', false);
});

it('shows the 404 page to a confirmed user on a missing fanart', function () {
    $this->actingAs(alice($this));

    $resp = $this->get('/fanarts/9999');

    $resp->assertNotFound();
    $resp->assertSee(__('shared::errors.actions.go_to_dashboard'), false);
});

it('uses the signed-in user theme preference on a model-bound 404', function () {
    $user = alice($this);
    $case = collect(Theme::cases())->first(fn ($t) => $t !== Theme::seasonal());
    setSettingsValue(
        $user->id,
        SharedServiceProvider::TAB_GENERAL,
        SharedServiceProvider::KEY_THEME,
        $case->value
    );

    // Requests made while building the user (login...) leave a theme shared in the
    // test process; a real request starts with none.
    View::share('theme', null);

    $resp = $this->actingAs($user)->get('/profile/nobody-xx');

    $resp->assertNotFound();
    $resp->assertSee("images/themes/{$case->value}/errors/404.png", false);
});

it('renders the 419 page without a resolved theme', function () {
    $html = view('shared::errors.419')->render();

    expect($html)->toContain(Theme::seasonal()->value);
});

it('renders the 500 page without a resolved theme', function () {
    $html = view('shared::errors.500')->render();

    expect($html)->toContain(Theme::seasonal()->value);
});
