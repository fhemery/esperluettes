<?php

use App\Domains\Auth\Public\Events\UserLoggedOut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Auth.UserLoggedOut event', function () {
    it('is emitted when a user logs out', function () {
        $user = alice($this); // default password is 'secret-password'
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');

        /* @var UserLoggedOut $event */
        $event = latestEventOf(UserLoggedOut::name(), UserLoggedOut::class);
        expect($event)->not->toBeNull();
        expect($event)->toBeInstanceOf(UserLoggedOut::class);
        expect($event->toPayload()['userId'] ?? null)->toBe($user->id);
    });
});
