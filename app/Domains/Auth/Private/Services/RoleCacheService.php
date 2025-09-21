<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use Illuminate\Support\Facades\Cache;

class RoleCacheService
{
    private const CACHE_KEY_PREFIX = 'auth:user_roles:'; // auth:user_roles:{userId}
    private const TTL_SECONDS = 600; // 10 minutes

    /**
     * @param array<int,int> $userIds
     * @return array<int,array<int,\App\Domains\Auth\Private\Models\Role>>
     */
    public function fetchByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return [];
        }

        // Prepare cache keys
        $keys = array_map(fn (int $id) => $this->key($id), $userIds);
        $cached = Cache::many($keys);

        // Map back keys to userIds
        $idByKey = array_combine($keys, $userIds);

        $result = [];
        $missingIds = [];

        foreach ($cached as $key => $value) {
            $id = $idByKey[$key];
            if ($value === null) {
                $missingIds[] = $id;
                continue;
            }
            // Value is an array of Role models (or empty array)
            $result[$id] = is_array($value) ? $value : [];
        }

        // Load missing from DB and fill cache
        if (!empty($missingIds)) {
            $users = User::query()
                ->with('roles')
                ->whereIn('id', $missingIds)
                ->get(['id']);

            $foundIds = [];
            foreach ($users as $user) {
                $roles = $user->roles->all(); // array of Role models
                $result[$user->id] = $roles;
                Cache::put($this->key((int)$user->id), $roles, self::TTL_SECONDS);
                $foundIds[] = (int) $user->id;
            }

            // For non-existent users, cache empty arrays to avoid repeated misses
            $missingStill = array_diff($missingIds, $foundIds);
            foreach ($missingStill as $id) {
                $result[$id] = [];
                Cache::put($this->key((int)$id), [], self::TTL_SECONDS);
            }
        }

        // Ensure all requested IDs exist as keys
        foreach ($userIds as $id) {
            if (!array_key_exists($id, $result)) {
                $result[$id] = [];
            }
        }

        return $result;
    }

    /**
     * @return array<int,\App\Domains\Auth\Private\Models\Role>
     */
    public function fetchByUserId(int $userId): array
    {
        $userId = (int) $userId;
        $cached = Cache::get($this->key($userId));
        if ($cached !== null) {
            return is_array($cached) ? $cached : [];
        }

        $user = User::query()->with('roles')->find($userId, ['id']);
        if (!$user) {
            Cache::put($this->key($userId), [], self::TTL_SECONDS);
            return [];
        }

        $roles = $user->roles->all();
        Cache::put($this->key($userId), $roles, self::TTL_SECONDS);
        return $roles;
    }

    public function clearForUser(int $userId): void
    {
        Cache::forget($this->key((int)$userId));
    }

    private function key(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }
}
