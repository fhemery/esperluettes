<?php

namespace App\Domains\Story\Public\Visibility;

use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;
use App\Domains\Story\Private\Services\ProfileCommentsPolicy;

/**
 * The comments tab follows Story's own comment privacy rule, which covers the
 * confirmed-user requirement, the owner, the moderator bypass and the owner's
 * "hide my comments" setting.
 */
class CommentsTabVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly ProfileCommentsPolicy $policy,
    ) {
    }

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $this->policy->canViewComments($ownerUserId, $viewerId);
    }
}
