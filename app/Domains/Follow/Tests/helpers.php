<?php

use App\Domains\Follow\Private\Models\Follow;
use App\Domains\Follow\Private\Repositories\FollowRepository;

function followUser(int $followerId, int $followedId): void
{
    Follow::create([
        'follower_id' => $followerId,
        'followed_id' => $followedId,
        'created_at' => now(),
    ]);
}

function assertFollowing(int $followerId, int $followedId): void
{
    expect(app(FollowRepository::class)->isFollowing($followerId, $followedId))->toBeTrue();
}

function assertNotFollowing(int $followerId, int $followedId): void
{
    expect(app(FollowRepository::class)->isFollowing($followerId, $followedId))->toBeFalse();
}
