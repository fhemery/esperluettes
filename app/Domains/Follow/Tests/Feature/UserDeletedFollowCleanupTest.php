<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('UserDeleted follow cleanup', function () {
    it('removes all follow entries where the deleted user was the follower', function () {
        $follower = alice($this);
        $followed1 = bob($this);
        $followed2 = carol($this);

        followUser($follower->id, $followed1->id);
        followUser($follower->id, $followed2->id);

        deleteUser($this, $follower);

        assertNotFollowing($follower->id, $followed1->id);
        assertNotFollowing($follower->id, $followed2->id);
    });

    it('removes all follow entries where the deleted user was being followed', function () {
        $followed = alice($this);
        $follower1 = bob($this);
        $follower2 = carol($this);

        followUser($follower1->id, $followed->id);
        followUser($follower2->id, $followed->id);

        deleteUser($this, $followed);

        assertNotFollowing($follower1->id, $followed->id);
        assertNotFollowing($follower2->id, $followed->id);
    });
});
