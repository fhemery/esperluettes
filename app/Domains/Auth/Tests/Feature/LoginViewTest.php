<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows the login view with create account CTA', function () {
    $this->assertGuest();

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee(__('auth::login.no_account'));
    $response->assertSee(__('auth::login.create_one'));
});
