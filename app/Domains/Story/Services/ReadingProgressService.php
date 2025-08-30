<?php

namespace App\Domains\Story\Services;

use App\Domains\Story\Models\ReadingProgress;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Support\Facades\DB;

class ReadingProgressService
{
    public function isChapterReadByUser(int $userId, int $chapterId): bool
    {
        return ReadingProgress::query()
            ->where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->exists();
    }

    public function markRead(int $userId, Story $story, Chapter $chapter): void
    {
        DB::transaction(function () use ($userId, $story, $chapter) {
            $existing = ReadingProgress::query()
                ->where('user_id', $userId)
                ->where('chapter_id', (int) $chapter->id)
                ->first();

            if ($existing) {
                return; // idempotent
            }

            ReadingProgress::query()->create([
                'user_id' => $userId,
                'story_id' => (int) $story->id,
                'chapter_id' => (int) $chapter->id,
                'read_at' => now(),
            ]);

            $chapter->increment('reads_logged_count');
        });
    }

    public function unmarkRead(int $userId, Story $story, Chapter $chapter): void
    {
        DB::transaction(function () use ($userId, $chapter) {
            $deleted = ReadingProgress::query()
                ->where('user_id', $userId)
                ->where('chapter_id', (int) $chapter->id)
                ->delete();

            if ($deleted) {
                $chapter->refresh();
                if ((int) $chapter->reads_logged_count > 0) {
                    $chapter->decrement('reads_logged_count');
                }
            }
        });
    }

    /**
     * Return the list of read chapter IDs for a given user within a given story.
     *
     * @param int $userId
     * @param int $storyId
     * @return array<int,int>
     */
    public function getReadChapterIdsForUserInStory(int $userId, int $storyId): array
    {
        return ReadingProgress::query()
            ->where('user_id', $userId)
            ->where('story_id', $storyId)
            ->pluck('chapter_id')
            ->map(fn($v) => (int)$v)
            ->all();
    }
}
