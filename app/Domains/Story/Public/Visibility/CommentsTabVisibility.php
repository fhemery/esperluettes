<?php

namespace App\Domains\Story\Public\Visibility;

use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;
use App\Domains\Shared\Contracts\ProfilePublicApi;

/**
 * The comments tab follows the profile's comment privacy rule, which already
 * covers the confirmed-user requirement, the owner, the moderator bypass and
 * the owner's "hide my comments" setting.
 */
class CommentsTabVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly ProfilePublicApi $profileApi,
    ) {
    }

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        if ($viewerId === null) {
            return false;
        }

        return $this->profileApi->canViewComments($ownerUserId, $viewerId);
    }
}
