<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Models\Story;
use App\Domains\Shared\ViewModels\SeoViewModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChapterViewModel
{
    public function __construct(
        public readonly Story $story,
        public readonly Chapter $chapter,
        public readonly bool $isAuthor,
        public readonly ?Chapter $prevChapter,
        public readonly ?Chapter $nextChapter,
        public readonly bool $isReadByMe,
        public readonly int $readsLogged,
        public readonly SeoViewModel $seo,
    ) {
    }

    public static function from(Story $story, Chapter $chapter, bool $isAuthor, bool $isReadByMe = false): self
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

        $rawTitle = ($story->title ?? '') . ' — ' . ($chapter->title ?? '');
        $pageTitle = Str::limit(strip_tags($rawTitle), 160, '');
        $coverImage = asset('images/story/default-cover.svg');

        return new self(
            story: $story,
            chapter: $chapter,
            isAuthor: $isAuthor,
            prevChapter: $prevChapter ?: null,
            nextChapter: $nextChapter ?: null,
            isReadByMe: $isReadByMe,
            readsLogged: (int) $chapter->reads_logged_count,
            seo: new SeoViewModel(title: $pageTitle, coverImage: $coverImage),
        );
    }
}
