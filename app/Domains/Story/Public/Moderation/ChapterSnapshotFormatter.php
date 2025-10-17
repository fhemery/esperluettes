<?php

namespace App\Domains\Story\Public\Moderation;

use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Story\Private\Models\Chapter;

class ChapterSnapshotFormatter implements SnapshotFormatterInterface
{
    public function capture(int $entityId): array
    {
        /** @var Chapter|null $chapter */
        $chapter = Chapter::find($entityId);
        if (! $chapter) {
            return [];
        }

        return [
            'title' => $chapter->title,
            'content' => $chapter->content,
        ];
    }

    public function render(array $snapshot): string
    {
        return view('story::moderation.chapter-snapshot', [
            'title' => (string)($snapshot['title'] ?? ''),
            'content' => (string)($snapshot['content'] ?? ''),
        ])->render();
    }

    public function getReportedUserId(int $entityId): int
    {
        /** @var Chapter|null $chapter */
        $chapter = Chapter::with('story')->find($entityId);
        return $chapter && $chapter->story ? (int)$chapter->story->created_by_user_id : 0;
    }

    public function getContentUrl(int $entityId): string
    {
        /** @var Chapter|null $chapter */
        $chapter = Chapter::with('story')->find($entityId);
        if (! $chapter || ! $chapter->story) {
            return '/';
        }
        return route('chapters.show', [
            'storySlug' => $chapter->story->slug,
            'chapterSlug' => $chapter->slug,
        ]);
    }
}
