<?php

namespace App\Domains\Fanart\Private\Services;

use App\Domains\Fanart\Private\Models\Fanartist;

class FanartistService
{
    public function isFanartist(int $userId, int $storyId): bool
    {
        return Fanartist::query()
            ->where('story_id', $storyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /** @return int[] */
    public function listFanartistIds(int $storyId): array
    {
        return Fanartist::query()
            ->where('story_id', $storyId)
            ->orderBy('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
