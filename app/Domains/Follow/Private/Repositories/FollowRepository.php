<?php

namespace App\Domains\Follow\Private\Repositories;

use App\Domains\Follow\Private\Models\Follow;
use Illuminate\Support\Facades\DB;

class FollowRepository
{
    public function isFollowing(int $followerId, int $followedId): bool
    {
        return Follow::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();
    }

    public function follow(int $followerId, int $followedId): bool
    {
        $existing = Follow::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();

        if ($existing) {
            return false;
        }

        Follow::create([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
            'created_at' => now(),
        ]);

        return true;
    }

    public function unfollow(int $followerId, int $followedId): void
    {
        Follow::where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete();
    }

    /** @return int[] */
    public function getFollowingIds(int $followerId): array
    {
        return Follow::where('follower_id', $followerId)
            ->pluck('followed_id')
            ->map('intval')
            ->all();
    }

    /** @return int[] */
    public function getFollowerIds(int $followedId): array
    {
        return Follow::where('followed_id', $followedId)
            ->pluck('follower_id')
            ->map('intval')
            ->all();
    }
}
