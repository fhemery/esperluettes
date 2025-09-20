<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\RoleCacheService;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Events\UserRoleGranted;
use App\Domains\Auth\Events\UserRoleRevoked;
use Illuminate\Support\Facades\Auth as AuthFacade;

class RoleService
{
    public function __construct(
        private readonly RoleCacheService $roleCache,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Grant a role to the user (idempotent) and clear role cache.
     */
    public function grant(User $user, string $role): void
    {
        $hadRole = $user->hasRole($role);
        $user->assignRole($role);
        $this->roleCache->clearForUser($user->id);

        // Verify in DB whether role is now attached (avoid stale in-memory relations)
        $nowHasRole = $user->roles()->where('slug', $role)->exists();
        if (!$hadRole && $nowHasRole) {
            $actorId = AuthFacade::id();
            $targetIsAdmin = $user->hasRole('admin');
            $this->eventBus->emit(new UserRoleGranted(
                userId: (int) $user->id,
                role: $role,
                actorUserId: $actorId !== null ? (int) $actorId : null,
                targetIsAdmin: $targetIsAdmin,
            ));
        }
    }

    /**
     * Revoke a role from the user (idempotent) and clear role cache.
     */
    public function revoke(User $user, string $role): void
    {
        $hadRole = $user->hasRole($role);
        $user->removeRole($role);
        $this->roleCache->clearForUser($user->id);

        // Verify in DB whether role is now detached (avoid stale in-memory relations)
        $nowHasRole = $user->roles()->where('slug', $role)->exists();
        if ($hadRole && !$nowHasRole) {
            $actorId = AuthFacade::id();
            $targetIsAdmin = $user->hasRole('admin');
            $this->eventBus->emit(new UserRoleRevoked(
                userId: (int) $user->id,
                role: $role,
                actorUserId: $actorId !== null ? (int) $actorId : null,
                targetIsAdmin: $targetIsAdmin,
            ));
        }
    }

    /**
     * Convenience promotion from probationary to confirmed.
     */
    public function promoteToConfirmed(User $user, string $probationRole, string $confirmedRole): void
    {
        $this->revoke($user, $probationRole);
        $this->grant($user, $confirmedRole);
    }

    /**
     * Convenience demotion to probationary.
     */
    public function demoteToUser(User $user, string $probationRole, string $confirmedRole): void
    {
        $this->revoke($user, $confirmedRole);
        $this->grant($user, $probationRole);
    }
}
