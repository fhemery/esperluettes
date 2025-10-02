<?php

namespace App\Domains\Message\Private\Services;

use App\Domains\Message\Private\Models\MessageDelivery;
use Illuminate\Support\Facades\Cache;

class UnreadCounterService
{
    /**
     * Get unread message count for a user.
     * Cached for performance with 5-minute TTL.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        $cacheKey = $this->getCacheKey($userId);

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return MessageDelivery::forUser($userId)
                ->unread()
                ->count();
        });
    }

    /**
     * Invalidate the unread count cache for a user.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateCache(int $userId): void
    {
        Cache::forget($this->getCacheKey($userId));
    }

    /**
     * Get the cache key for a user's unread count.
     *
     * @param int $userId
     * @return string
     */
    private function getCacheKey(int $userId): string
    {
        return "message_unread_count_{$userId}";
    }

    /**
     * Check if user has any messages (read or unread).
     *
     * @param int $userId
     * @return bool
     */
    public function hasAnyMessages(int $userId): bool
    {
        return MessageDelivery::forUser($userId)->exists();
    }
}
