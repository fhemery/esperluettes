<?php

namespace App\Domains\Auth\Private\Services;

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Models\Role;
use App\Domains\Auth\Private\Services\RoleCacheService;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserRoleGranted;
use App\Domains\Auth\Public\Events\UserRoleRevoked;
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
            $targetIsAdmin = $user->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]);
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
            $targetIsAdmin = $user->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]);
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

    /**
     * Search roles by partial name or slug.
     *
     * @return array<int, Role>
     */
    public function searchByName(string $q, int $limit = 25): array
    {
        $term = trim($q);
        if ($term === '') {
            return [];
        }
        $cap = max(1, min(50, (int) $limit));
        $like = "%{$term}%";
        return Role::query()
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('slug', 'like', $like);
            })
            ->orderBy('name')
            ->limit($cap)
            ->get()
            ->all();
    }

    /**
     * Fetch roles by slugs.
     *
     * @param array<int,string> $slugs
     * @return array<int, Role>
     */
    public function getBySlugs(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map(function ($s) {
            return is_string($s) ? trim($s) : '';
        }, $slugs), fn ($s) => $s !== '')));
        if (empty($slugs)) {
            return [];
        }
        return Role::query()
            ->whereIn('slug', $slugs)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * List all roles ordered by name.
     *
     * @return array<int, Role>
     */
    public function all(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->all();
    }
}
