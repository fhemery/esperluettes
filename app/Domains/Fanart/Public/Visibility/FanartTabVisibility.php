<?php

namespace App\Domains\Fanart\Public\Visibility;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Profile\Public\Contracts\ProfileTabVisibility;

class FanartTabVisibility implements ProfileTabVisibility
{
    public function __construct(
        private readonly FanartPolicy $policy,
    ) {
    }

    public function isVisible(int $ownerUserId, ?int $viewerId): bool
    {
        return $this->policy->seeProfileTab($viewerId, $ownerUserId);
    }
}
