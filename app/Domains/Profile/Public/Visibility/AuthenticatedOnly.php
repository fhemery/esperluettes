<?php

namespace App\Domains\Profile\Public\Visibility;

use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;

/**
 * A tab any logged-in user can see, hidden from guests.
 */
class AuthenticatedOnly implements ProfileTabVisibility
{
    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $viewerId !== null;
    }
}
