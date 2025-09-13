<?php

use App\Domains\Auth\Events\PasswordChanged;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Auth.PasswordChanged event', function () {
    it('is emitted when an authenticated user updates their password', function () {
        $user = alice($this); // default password is 'secret-password'
        $this->actingAs($user);

        $response = $this->from('/account')->put(route('password.update'), [
            'current_password' => 'secret-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect('/account');

        $event = latestEventOf(PasswordChanged::name(), PasswordChanged::class);
        expect($event)->not->toBeNull();
        expect($event)->toBeInstanceOf(PasswordChanged::class);
        expect($event->toPayload()['userId'] ?? null)->toBe($user->id);
    });

    it('is emitted when a user resets password via token', function () {
        $user = alice($this);
        // Generate a valid token
        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'another-strong-password',
            'password_confirmation' => 'another-strong-password',
        ]);

        $response->assertRedirect(route('login'));

        $event = latestEventOf(PasswordChanged::name(), PasswordChanged::class);
        expect($event)->not->toBeNull();
        expect($event)->toBeInstanceOf(PasswordChanged::class);
        expect($event->toPayload()['userId'] ?? null)->toBe($user->id);
    });
});
