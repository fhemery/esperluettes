<?php

namespace App\Domains\Profile\Public\Visibility;

use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;

/**
 * A tab everyone can see, including guests.
 */
class AlwaysVisible implements ProfileTabVisibility
{
    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return true;
    }
}
