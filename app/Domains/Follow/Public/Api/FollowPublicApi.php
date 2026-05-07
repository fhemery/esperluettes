<?php

namespace App\Domains\Follow\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Follow\Private\Repositories\FollowRepository;
use App\Domains\Settings\Public\Api\SettingsPublicApi;

class FollowPublicApi
{
    public function __construct(
        private FollowRepository $repository,
        private SettingsPublicApi $settings,
        private AuthPublicApi $authApi,
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

        $rolesById = $this->authApi->getRolesByUserIds([$viewerUserId]);
        $isConfirmed = collect($rolesById[$viewerUserId] ?? [])
            ->contains(fn($r) => $r->slug === Roles::USER_CONFIRMED);

        if (!$isConfirmed) {
            return false;
        }

        $hidden = (bool) $this->settings->getValue(
            $profileUserId,
            'profile',
            'hide-following-tab',
        );

        return !$hidden;
    }
}
