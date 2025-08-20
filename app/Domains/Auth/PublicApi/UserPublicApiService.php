<?php

namespace App\Domains\Auth\PublicApi;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\PublicApi\Dto\RoleDto;

class UserPublicApiService implements UserPublicApi
{
    /**
     * @param array<int,int> $userIds
     * @return array<int,array<int,RoleDto>>
     */
    public function getRolesByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return [];
        }

        $users = User::query()
            ->with('roles')
            ->whereIn('id', $userIds)
            ->get(['id']);

        $result = [];
        foreach ($users as $user) {
            $result[$user->id] = $user->roles
                ->map(fn ($role) => RoleDto::fromModel($role))
                ->all();
        }

        // Ensure all requested IDs exist as keys (with empty roles) to make the API predictable
        foreach ($userIds as $id) {
            if (!array_key_exists($id, $result)) {
                $result[$id] = [];
            }
        }

        return $result;
    }
}
