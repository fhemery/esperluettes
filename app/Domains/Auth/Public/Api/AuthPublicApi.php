<?php

namespace App\Domains\Auth\Public\Api;

use App\Domains\Auth\Public\Api\Dto\RoleDto;
use App\Domains\Auth\Private\Services\RoleCacheService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AuthPublicApi
{
    public function __construct(
        private RoleCacheService $roleCache,
    ) {}

    /**
     * @param array<int,int> $userIds
     * @return array<int,array<int,RoleDto>>
     */
    public function getRolesByUserIds(array $userIds): array
    {
        $byId = $this->roleCache->fetchByUserIds($userIds);
        $result = [];
        foreach ($byId as $userId => $roles) {
            $result[$userId] = array_map(fn ($role) => RoleDto::fromModel($role), $roles);
        }
        // Ensure predictable keys for requested ids even if cache/db misses
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        foreach ($userIds as $id) {
            if (!array_key_exists($id, $result)) {
                $result[$id] = [];
            }
        }
        return $result;
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    public function isVerified(?Authenticatable $user): bool
    {
        if (!$user) {
            $user=  Auth::user();
        }
       return $this->hasAnyRole([Roles::USER, Roles::USER_CONFIRMED]);
    }

    public function hasAnyRole(array $roles): bool
    {
        /** @var \App\Domains\Auth\Private\Models\User|null */
        $user = Auth::user() ;
        if (!$user || !$user->hasRole($roles)) {
            return false;
        }
        return true;
    }
}
