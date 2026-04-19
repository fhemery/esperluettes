<?php

namespace App\Domains\Story\Private\Policies;

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Domains\Story\Private\Models\Story;

class StoryPolicy
{
    /**
     * Determine whether the user can view the story according to visibility.
     * - public: everyone
     * - community: confirmed users only
     * - private: collaborators only
     * - moderators/admins can always view any story
     */
    public function view(?Authenticatable $user, Story $story): bool
    {
        if ($user !== null && method_exists($user, 'hasRole') &&
            $user->hasRole([Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN])) {
            return true;
        }

        if ($story->visibility === Story::VIS_PUBLIC) {
            return true;
        }

        if ($story->visibility === Story::VIS_COMMUNITY) {
            return $user !== null && method_exists($user, 'isConfirmed') && $user->isConfirmed();
        }

        if ($story->visibility === Story::VIS_PRIVATE) {
            return $user !== null && $story->isCollaborator($user->id);
        }

        // default deny for unknown visibilities
        return false;
    }
}
