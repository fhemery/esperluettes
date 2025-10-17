<?php

namespace App\Domains\Auth\Public\Api;

use App\Domains\Auth\Public\Api\Dto\RoleDto;
use App\Domains\Auth\Private\Services\RoleCacheService;
use App\Domains\Auth\Private\Services\UserQueryService;
use App\Domains\Auth\Private\Services\RoleService;
use App\Domains\Auth\Private\Services\UserService;
use App\Domains\Auth\Private\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AuthPublicApi
{
    public function __construct(
        private RoleCacheService $roleCache,
        private UserQueryService $userQuery,
        private RoleService $roleService,
        private UserService $userService,
    ) {}

    /**
     * @param array<int,int> $userIds
     * @return array<int,array<RoleDto>>
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

    /**
     * Get user IDs for users who have any of the specified roles.
     *
     * @param array<string> $roleSlugs Role slugs to filter by
     * @param bool $activeOnly If true, only return active users (default: true)
     * @return array<int> Array of user IDs
     */
    public function getUserIdsByRoles(array $roleSlugs, bool $activeOnly = true): array
    {
        return $this->userQuery->getUserIdsByRoles($roleSlugs, $activeOnly);
    }

    /**
     * Get all active user IDs.
     *
     * @return array<int> Array of user IDs
     */
    public function getAllActiveUserIds(): array
    {
        return $this->userQuery->getAllActiveUserIds();
    }

    /**
     * Get all roles as RoleDto, ordered by name.
     *
     * @return array<int, RoleDto>
     */
    public function getAllRoles(): array
    {
        $roles = $this->roleService->all();
        return array_map(fn ($r) => RoleDto::fromModel($r), $roles);
    }

    /**
     * Delete a user by ID (admin or tech-admin only).
     *
     * @throws AuthorizationException
     */
    public function deleteUserById(int $userId): void
    {
        if (! $this->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to delete users.');
        }

        /** @var User $user */
        $user = User::query()->findOrFail($userId);
        $this->userService->deleteUser($user);
    }

    /**
     * Deactivate a user by ID (admin or tech-admin only).
     *
     * @throws AuthorizationException
     */
    public function deactivateUserById(int $userId): void
    {
        if (! $this->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to deactivate users.');
        }

        /** @var User $user */
        $user = User::query()->findOrFail($userId);
        $this->userService->deactivateUser($user);
    }

    /**
     * Activate a user by ID (admin or tech-admin only).
     *
     * @throws AuthorizationException
     */
    public function activateUserById(int $userId): void
    {
        if (! $this->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN])) {
            throw new AuthorizationException('You are not authorized to activate users.');
        }

        /** @var User $user */
        $user = User::query()->findOrFail($userId);
        $this->userService->activateUser($user);
    }
}
