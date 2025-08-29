<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use Illuminate\Support\Collection;

class ChapterViewModel
{
    public function __construct(
        public readonly Story $story,
        public readonly Chapter $chapter,
        public readonly bool $isAuthor,
        public readonly ?Chapter $prevChapter,
        public readonly ?Chapter $nextChapter,
    ) {
    }

    public static function from(Story $story, Chapter $chapter, bool $isAuthor): self
    {
        // Chapters should be eager-loaded and ordered by sort_order
        /** @var Collection<int, Chapter> $chapters */
        $chapters = $story->relationLoaded('chapters')
            ? $story->chapters
            : collect();

        $navChapters = $isAuthor
            ? $chapters
            : $chapters->where('status', Chapter::STATUS_PUBLISHED)->values();

        $prevChapter = $navChapters
            ->where('sort_order', '<', $chapter->sort_order)
            ->sortByDesc('sort_order')
            ->first();

        $nextChapter = $navChapters
            ->where('sort_order', '>', $chapter->sort_order)
            ->sortBy('sort_order')
            ->first();

        return new self(
            story: $story,
            chapter: $chapter,
            isAuthor: $isAuthor,
            prevChapter: $prevChapter ?: null,
            nextChapter: $nextChapter ?: null,
        );
    }

    public function toArray(): array
    {
        return [
            'story' => $this->story,
            'chapter' => $this->chapter,
            'prevChapter' => $this->prevChapter,
            'nextChapter' => $this->nextChapter,
            'isAuthor' => $this->isAuthor,
        ];
    }
}
