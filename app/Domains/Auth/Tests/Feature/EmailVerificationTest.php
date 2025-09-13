<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('email verification request', function () {
    it('sends verification email to unverified user', function () {
        Notification::fake();

        $user = alice($this, [], false);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);

        expect(session('status'))
            ->toBe('verification-link-sent');
    });

    it('does not send verification if already verified and redirects to dashboard', function () {
        $user = alice($this, [], true);

        Notification::fake();
        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect(route('dashboard'));

        Notification::assertNothingSent();
    });
});
