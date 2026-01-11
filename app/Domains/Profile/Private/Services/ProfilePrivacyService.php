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
        // If no viewer, comments are not visible
        if ($viewerUserId === null) {
            return false;
        }

        // Owner can always see their own comments
        if ($viewerUserId === $profileUserId) {
            return true;
        }

        // Get viewer roles
        $rolesById = $this->authApi->getRolesByUserIds([$viewerUserId]);
        $viewerRoles = $rolesById[$viewerUserId] ?? [];

        // Check if viewer is confirmed (has USER_CONFIRMED role)
        $isConfirmed = false;
        foreach ($viewerRoles as $roleDto) {
            if ($roleDto->slug === Roles::USER_CONFIRMED) {
                $isConfirmed = true;
                break;
            }
        }

        // If viewer is not confirmed and not privileged, they cannot see others' comments
        if (!$isConfirmed && !$this->hasPrivilegedRole($viewerRoles)) {
            return false;
        }

        // Check if the profile owner has hidden their comments section
        $isHidden = (bool) $this->settingsApi->getValue(
            $profileUserId,
            ProfileServiceProvider::TAB_PROFILE,
            ProfileServiceProvider::KEY_HIDE_COMMENTS_SECTION
        );

        // If not hidden, confirmed/privileged users can see
        if (!$isHidden) {
            return true;
        }

        // If hidden, only privileged users can see
        if ($this->hasPrivilegedRole($viewerRoles)) {
            return true;
        }

        return false;
    }

    private function hasPrivilegedRole(array $viewerRoles): bool
    {
        $privilegedRoles = [
            Roles::MODERATOR,
            Roles::ADMIN,
            Roles::TECH_ADMIN,
        ];

        foreach ($viewerRoles as $roleDto) {
            if (in_array($roleDto->slug, $privilegedRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
