<?php

namespace App\Domains\Story\Policies;

use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Contracts\Auth\Authenticatable as User;

class ChapterPolicy
{
    /**
     * Authors of the story can create chapters.
     */
    public function create(User $user, Story $story): bool
    {
        return $story->isAuthor((int)$user->id);
    }

    /**
     * Authors of the story can edit/update chapters.
     */
    public function edit(User $user, Chapter $chapter): bool
    {
        return $chapter->story && $chapter->story->isAuthor((int)$user->id);
    }
}
