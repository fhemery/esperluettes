<?php

namespace App\Domains\Story\Private\ViewModels;

use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Shared\ViewModels\SeoViewModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CurrentChapterViewModel {
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $authorNote,
        public readonly ?string $content,
        public readonly bool $isPublished,
        public readonly int $wordCount,
        public readonly int $characterCount,
        public readonly int $readsLogged,
    ) {
    }

    static function from(Chapter $chapter): self
    {
        return new self(
            id: $chapter->id,
            title: $chapter->title,
            slug: $chapter->slug,
            authorNote: $chapter->author_note,
            content: $chapter->content,
            isPublished: $chapter->status === Chapter::STATUS_PUBLISHED,
            wordCount: (int) ($chapter->word_count ?? 0),
            characterCount: (int) ($chapter->character_count ?? 0),
            readsLogged: (int) $chapter->reads_logged_count,
        );
    }
}

/**
 * Use to represent any chapter around the current one
 */
class ShortChapterViewModel {
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
    ) {
    }

    static function from(Chapter $chapter): self
    {
        return new self(
            title: $chapter->title,
            slug: $chapter->slug,
        );
    }
}

class ChapterStoryViewModel {
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly string $coverUrl,
        /** @var array<ShortChapterViewModel> */
        public readonly array $chapters,
    ) {
    }

    /**
     * @param array<Chapter> $chapters
     */
    static function from(Story $story, array $chapters) : self
    {
        return new self(
            title: $story->title,
            slug: $story->slug,
            coverUrl: $story->cover_url ?? asset('images/story/default-cover.svg'),
            chapters: array_map(fn(Chapter $chapter) => ShortChapterViewModel::from($chapter), $chapters),
        );
    }
}

class ChapterViewModel
{
    /** @var array<ProfileDto> $authors */
    public function __construct(
        public readonly ChapterStoryViewModel $story,
        public readonly CurrentChapterViewModel $chapter,
        public readonly bool $isAuthor,
        public readonly array $authors,
        public readonly ?ShortChapterViewModel $prevChapter,
        public readonly ?ShortChapterViewModel $nextChapter,
        public readonly bool $isReadByMe,
        public readonly int $readsLogged,
        public readonly int $wordCount,
        public readonly int $characterCount,
        public readonly SeoViewModel $seo,
    ) {
    }

    public static function from(Story $story, Chapter $chapter, bool $isAuthor, bool $isReadByMe = false, array $authors): self
    {
        // Chapters should be eager-loaded and ordered by sort_order
        /** @var Collection<int, Chapter> $chapters */
        $chapters = $story->chapters;

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
        $coverImage = $story->cover_url ?? asset('images/story/default-cover.svg');

        return new self(
            story: ChapterStoryViewModel::from($story, $chapters->all()),
            chapter: CurrentChapterViewModel::from($chapter),
            isAuthor: $isAuthor,
            authors: $authors,
            prevChapter: $prevChapter ? ShortChapterViewModel::from($prevChapter) : null,
            nextChapter: $nextChapter ? ShortChapterViewModel::from($nextChapter) : null,
            isReadByMe: $isReadByMe,
            readsLogged: (int) $chapter->reads_logged_count,
            wordCount: (int) ($chapter->word_count ?? 0),
            characterCount: (int) ($chapter->character_count ?? 0),
            seo: new SeoViewModel(title: $pageTitle, coverImage: $coverImage)
        );
    }
}
