<?php

namespace App\Domains\Profile\Public\Visibility;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;

/**
 * A tab visible only to viewers holding one of a fixed set of roles.
 *
 * The roles have to be baked in, so subclass this and implement roles():
 *
 *     class CommentsTabVisibility extends RoleBasedVisibility
 *     {
 *         protected function roles(): array { return [Roles::USER_CONFIRMED]; }
 *     }
 *
 * The check is on the *viewer*, not the profile owner: an owner who lacks the
 * role does not see the tab on their own profile either.
 */
abstract class RoleBasedVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly AuthPublicApi $authApi,
    ) {
    }

    /**
     * @return array<int, string> Role slugs, any one of which grants access.
     */
    abstract protected function roles(): array;

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        if ($viewerId === null) {
            return false;
        }

        $roles = $this->authApi->getRolesByUserIds([$viewerId])[$viewerId] ?? [];
        $slugs = array_map(static fn ($role) => $role->slug, $roles);

        return array_intersect($this->roles(), $slugs) !== [];
    }
}
