<?php

namespace App\Domains\Follow\Public\Visibility;

use App\Domains\Follow\Public\Api\FollowPublicApi;
use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;

class FollowingTabVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly FollowPublicApi $followApi,
    ) {
    }

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $this->followApi->canViewFollowingTab($ownerUserId, $viewerId);
    }
}
