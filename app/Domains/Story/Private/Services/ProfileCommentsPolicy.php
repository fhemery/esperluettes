<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Story\Public\Providers\StoryServiceProvider;

/**
 * Who may see a user's comment list on their profile.
 *
 * Story owns this because Story owns the comments tab: the rule, the setting
 * behind it and the tab itself all belong together. Profile only knows there
 * is a tab, via the registry.
 */
class ProfileCommentsPolicy
{
    public function __construct(
        private SettingsPublicApi $settingsApi,
        private AuthPublicApi $authApi,
    ) {
    }

    /**
     * @param  int  $profileUserId  The user whose comments are being listed.
     * @param  int|null  $viewerUserId  The viewer, or null for a guest.
     */
    public function canViewComments(int $profileUserId, ?int $viewerUserId): bool
    {
        if ($viewerUserId === null) {
            return false;
        }

        // The owner always sees their own comments.
        if ($viewerUserId === $profileUserId) {
            return true;
        }

        $rolesById = $this->authApi->getRolesByUserIds([$viewerUserId]);
        $viewerRoles = $rolesById[$viewerUserId] ?? [];

        $isConfirmed = false;
        foreach ($viewerRoles as $roleDto) {
            if ($roleDto->slug === Roles::USER_CONFIRMED) {
                $isConfirmed = true;
                break;
            }
        }

        if (!$isConfirmed && !$this->hasPrivilegedRole($viewerRoles)) {
            return false;
        }

        $isHidden = (bool) $this->settingsApi->getValue(
            $profileUserId,
            StoryServiceProvider::TAB_PROFILE,
            StoryServiceProvider::KEY_HIDE_COMMENTS_SECTION
        );

        if (!$isHidden) {
            return true;
        }

        // Moderators and admins see through the setting.
        return $this->hasPrivilegedRole($viewerRoles);
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
