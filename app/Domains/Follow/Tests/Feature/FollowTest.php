<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Follow', function () {
    it('redirects guests to login', function () {
        $target = alice($this);

        $this->post(route('follow.follow', $target->id))
            ->assertRedirect('/login');
    });

    it('denies non-verified users', function () {
        $viewer = bob($this, roles: []);
        $target = alice($this);

        $this->actingAs($viewer)
            ->post(route('follow.follow', $target->id))
            ->assertRedirect(route('dashboard'));
    });

    it('allows a confirmed user to follow another user', function () {
        $follower = bob($this);
        $target = alice($this);

        $this->actingAs($follower)
            ->post(route('follow.follow', $target->id))
            ->assertRedirect();

        assertFollowing($follower->id, $target->id);
    });

    it('allows a non-confirmed but verified user to follow', function () {
        $follower = bob($this, roles: [Roles::USER]);
        $target = alice($this);

        $this->actingAs($follower)
            ->post(route('follow.follow', $target->id))
            ->assertRedirect();

        assertFollowing($follower->id, $target->id);
    });

    it('cannot follow oneself', function () {
        $user = alice($this);

        $this->actingAs($user)
            ->post(route('follow.follow', $user->id))
            ->assertRedirect();

        assertNotFollowing($user->id, $user->id);
    });

    it('is idempotent — following twice does not create a duplicate', function () {
        $follower = bob($this);
        $target = alice($this);

        $this->actingAs($follower)->post(route('follow.follow', $target->id));
        $this->actingAs($follower)->post(route('follow.follow', $target->id));

        $this->assertDatabaseCount('follow_follows', 1);
    });
});
