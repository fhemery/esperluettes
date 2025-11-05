<?php

namespace App\Domains\ReadList\Private\Services;

use App\Domains\ReadList\Private\Models\ReadListEntry;

class ReadListService
{
    public function hasStory(int $userId, int $storyId): bool
    {
        return ReadListEntry::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->exists();
    }

    public function addStory(int $userId, int $storyId): bool
    {
        $exists = $this->hasStory($userId, $storyId);
        
        if ($exists) {
            return false;
        }

        ReadListEntry::create([
            'user_id' => $userId,
            'story_id' => $storyId,
        ]);

        return true;
    }

    public function removeStory(int $userId, int $storyId): void
    {
        ReadListEntry::where('user_id', $userId)
            ->where('story_id', $storyId)
            ->delete();
    }
}
