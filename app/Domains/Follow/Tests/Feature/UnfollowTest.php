<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Unfollow', function () {
    it('redirects guests to login', function () {
        $target = alice($this);

        $this->delete(route('follow.unfollow', $target->id))
            ->assertRedirect('/login');
    });

    it('removes the follow relationship', function () {
        $follower = bob($this);
        $target = alice($this);
        followUser($follower->id, $target->id);

        $this->actingAs($follower)
            ->delete(route('follow.unfollow', $target->id))
            ->assertRedirect();

        assertNotFollowing($follower->id, $target->id);
    });

    it('is idempotent — unfollowing a non-followed user does nothing', function () {
        $follower = bob($this);
        $target = alice($this);

        $this->actingAs($follower)
            ->delete(route('follow.unfollow', $target->id))
            ->assertRedirect();

        assertNotFollowing($follower->id, $target->id);
    });

    it('does not send a notification on unfollow', function () {
        $follower = bob($this);
        $target = alice($this);
        followUser($follower->id, $target->id);

        $this->actingAs($follower)
            ->delete(route('follow.unfollow', $target->id));

        $this->assertDatabaseCount('notifications', 0);
    });
});
