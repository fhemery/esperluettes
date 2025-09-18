<?php

namespace App\Domains\Profile\Private\Services;

use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Support\Facades\Cache;

class ProfileCacheService
{
    private int $ttlSeconds;

    public function __construct(int $ttlSeconds = 600)
    {
        $this->ttlSeconds = $ttlSeconds; // default 10 minutes
    }

    private function keyForUserId(int $userId): string
    {
        return "profile_by_user_id:" . $userId;
    }

    /**
     * Retrieve a Profile from cache for the given user ID.
     *
     * Returns:
     * - Profile instance when found
     * - null when explicitly cached as null (known missing)
     * - false when no cache entry exists (cache miss)
     */
    public function getByUserId(int $userId): Profile|false|null
    {
        $key = $this->keyForUserId($userId);
        if (!Cache::has($key)) {
            return false; // indicate cache miss distinctly
        }
        /** @var Profile|null $value */
        $value = Cache::get($key);
        return $value; // Profile or null
    }

    /**
     * Store a Profile (or null) in cache for the given user ID.
     */
    public function putByUserId(int $userId, ?Profile $profile): void
    {
        $key = $this->keyForUserId($userId);
        Cache::put($key, $profile, $this->ttlSeconds);
    }

    /**
     * Forget the cached Profile entry for a given user ID.
     */
    public function forgetByUserId(int $userId): void
    {
        $key = $this->keyForUserId($userId);
        Cache::forget($key);
    }
}
