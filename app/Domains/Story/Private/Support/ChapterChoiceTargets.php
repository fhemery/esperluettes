<?php

declare(strict_types=1);

namespace App\Domains\Story\Private\Support;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;

/**
 * The chapters a `chapter-choice` block of a story may point to: every
 * non-deleted chapter of that story, whatever its status, in reading order.
 */
class ChapterChoiceTargets
{
    /**
     * URLs are relative so the stored HTML survives a domain change; a later
     * slug change is absorbed by the canonical redirect of the reader page.
     *
     * @return array<int, array{id: int, title: string, url: string, published: bool}>
     */
    public function forStory(Story $story): array
    {
        $targets = [];
        $chapters = Chapter::query()
            ->where('story_id', $story->id)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'status']);

        foreach ($chapters as $chapter) {
            $targets[(int) $chapter->id] = [
                'id' => (int) $chapter->id,
                'title' => (string) $chapter->title,
                'url' => route('chapters.show', [
                    'storySlug' => $story->slug,
                    'chapterSlug' => $chapter->slug,
                ], false),
                'published' => $chapter->status === Chapter::STATUS_PUBLISHED,
            ];
        }

        return $targets;
    }
}
