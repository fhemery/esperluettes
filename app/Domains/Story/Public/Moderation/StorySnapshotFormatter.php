<?php

namespace App\Domains\Story\Public\Moderation;

use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Story\Private\Models\Story;

class StorySnapshotFormatter implements SnapshotFormatterInterface
{
    public function capture(int $entityId): array
    {
        /** @var Story|null $story */
        $story = Story::find($entityId);
        if (! $story) {
            return [];
        }

        return [
            'title' => $story->title,
            'summary' => $story->description,
        ];
    }

    public function render(array $snapshot): string
    {
        return '<div>' . __('story::moderation.title') . e($snapshot['title'] ?? '') . '</div>'
            . '<div>' . __('story::moderation.summary') . e($snapshot['summary'] ?? '') . '</div>';
    }

    public function getReportedUserId(int $entityId): int
    {
        /** @var Story|null $story */
        $story = Story::find($entityId);
        return $story ? (int) $story->created_by_user_id : 0;
    }

    public function getContentUrl(int $entityId): string
    {
        /** @var Story|null $story */
        $story = Story::find($entityId);
        if (! $story) {
            return '/';
        }
        return route('stories.show', ['slug' => $story->slug]);
    }
}
