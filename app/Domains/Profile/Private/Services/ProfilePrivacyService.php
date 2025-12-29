<?php

namespace App\Domains\Profile\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Public\Providers\ProfileServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;

class ProfilePrivacyService
{
    public function __construct(
        private SettingsPublicApi $settingsApi,
        private AuthPublicApi $authApi,
    ) {}

    /**
     * Check if the comments section should be visible for a given profile.
     *
     * @param int $profileUserId The user ID of the profile being viewed
     * @param int|null $viewerUserId The user ID of the viewer (null for guests)
     * @return bool True if comments should be visible
     */
    public function canViewComments(int $profileUserId, ?int $viewerUserId): bool
    {
        // Check if the profile owner has hidden their comments section
        $isHidden = (bool) $this->settingsApi->getValue(
            $profileUserId,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION
        );

        // If not hidden, everyone can see
        if (!$isHidden) {
            return true;
        }

        // If hidden, check if viewer has special access
        if ($viewerUserId === null) {
            return false;
        }

        // Owner can always see their own comments
        if ($viewerUserId === $profileUserId) {
            return true;
        }

        // Moderators, Admins, and Tech Admins can always see
        $privilegedRoles = [
            Roles::MODERATOR,
            Roles::ADMIN,
            Roles::TECH_ADMIN,
        ];

        $rolesById = $this->authApi->getRolesByUserIds([$viewerUserId]);
        $viewerRoles = $rolesById[$viewerUserId] ?? [];

        foreach ($viewerRoles as $roleDto) {
            if (in_array($roleDto->slug, $privilegedRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
