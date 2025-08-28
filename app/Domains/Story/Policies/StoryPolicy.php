<?php

namespace App\Domains\Story\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Domains\Story\Models\Story;

class StoryPolicy
{
    /**
     * Determine whether the user can view the story according to visibility.
     * - public: everyone
     * - community: confirmed users only (role: user-confirmed)
     * - private: collaborators only
     */
    public function view(?Authenticatable $user, Story $story): bool
    {
        if ($story->visibility === Story::VIS_PUBLIC) {
            return true;
        }

        if ($story->visibility === Story::VIS_COMMUNITY) {
            return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('user-confirmed');
        }

        if ($story->visibility === Story::VIS_PRIVATE) {
            return $user !== null && $story->isCollaborator($user->id);
        }

        // default deny for unknown visibilities
        return false;
    }
}
