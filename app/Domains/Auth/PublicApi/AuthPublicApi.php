<?php

namespace App\Domains\Auth\PublicApi;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\PublicApi\Dto\RoleDto;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AuthPublicApi
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
        /** @var \App\Domains\Auth\Models\User|null */
        $user = Auth::user() ;
        if (!$user || !$user->hasRole($roles)) {
            return false;
        }
        return true;
    }
}
