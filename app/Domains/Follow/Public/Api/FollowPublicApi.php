<?php

namespace App\Domains\Follow\Public\Api;

use App\Domains\Follow\Private\Repositories\FollowRepository;
use App\Domains\Settings\Public\Api\SettingsPublicApi;

class FollowPublicApi
{
    public function __construct(
        private FollowRepository $repository,
        private SettingsPublicApi $settings,
    ) {}

    /** @return int[] */
    public function getFollowerIds(int $followedId): array
    {
        return $this->repository->getFollowerIds($followedId);
    }

    public function canViewFollowingTab(int $profileUserId, ?int $viewerUserId): bool
    {
        if ($viewerUserId === null) {
            return false;
        }

        if ($viewerUserId === $profileUserId) {
            return true;
        }

        $hidden = (bool) $this->settings->getValue(
            $profileUserId,
            'profile',
            'hide-following-tab',
        );

        return !$hidden;
    }
}
