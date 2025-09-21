<?php

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
});

describe('Auth user deletion', function () {
    describe('Events', function () {
        it('emits Auth.UserDeleted when a user deletes their account', function () {
            /** @var User $user */
            $user = alice($this); // password is 'secret-password'
            $this->actingAs($user);

            $response = $this->delete(route('account.destroy'), [
                'password' => 'secret-password',
            ]);

            $response->assertRedirect('/');

            /** @var UserDeleted|null $event */
            $event = latestEventOf(UserDeleted::name(), UserDeleted::class);
            expect($event)->not->toBeNull();
            expect($event->userId)->toBe($user->id);
        });
    });
});
