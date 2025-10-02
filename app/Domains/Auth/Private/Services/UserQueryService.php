<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;

class UserQueryService
{
    /**
     * Get user IDs for users who have any of the specified roles.
     *
     * @param array<string> $roleSlugs Role slugs to filter by
     * @param bool $activeOnly If true, only return active users (default: true)
     * @return array<int> Array of user IDs
     */
    public function getUserIdsByRoles(array $roleSlugs, bool $activeOnly = true): array
    {
        if (empty($roleSlugs)) {
            return [];
        }

        $query = User::query()
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereIn('roles.slug', $roleSlugs);

        if ($activeOnly) {
            $query->where('users.is_active', true);
        }

        return $query->pluck('users.id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get all active user IDs.
     *
     * @return array<int> Array of user IDs
     */
    public function getAllActiveUserIds(): array
    {
        return User::query()
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }
}
