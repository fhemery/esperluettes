<?php

use App\Domains\Auth\Public\Events\UserLoggedIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Auth.UserLoggedIn event', function () {
    it('is emitted when a user logs in successfully', function () {
        $user = alice($this); // default password is 'secret-password'

        $response = $this->from('/login')->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect();

        /* @var UserLoggedIn $event */
        $event = latestEventOf(UserLoggedIn::name(), UserLoggedIn::class);
        expect($event)->not->toBeNull();
        expect($event)->toBeInstanceOf(UserLoggedIn::class);
        expect($event->toPayload()['userId'] ?? null)->toBe($user->id);
    });
});
