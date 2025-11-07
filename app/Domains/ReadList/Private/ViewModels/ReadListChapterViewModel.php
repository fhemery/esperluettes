<?php

namespace App\Domains\ReadList\Private\ViewModels;

use App\Domains\Story\Public\Contracts\StoryChapterDto;
use Illuminate\Support\Facades\Route;

class ReadListChapterViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly bool $isRead,
        public readonly string $url,
        public readonly string $updatedAt,
        public readonly int $wordCount,
        public readonly int $characterCount,
        public readonly int $readsLogged,
        public readonly int $commentCount,
    ) {}

    public static function fromDto(StoryChapterDto $dto, string $storySlug): self
    {
        return new self(
            id: $dto->id,
            title: $dto->title,
            slug: $dto->slug,
            isRead: $dto->isRead ?? false,
            url: route('chapters.show', ['storySlug' => $storySlug, 'chapterSlug' => $dto->slug]),
            updatedAt: $dto->firstPublishedAt?->format('c') ?? now()->format('c'),
            wordCount: $dto->wordCount,
            characterCount: $dto->characterCount,
            readsLogged: $dto->readsLoggedCount,
            commentCount: 0, // Not available in StoryChapterDto
        );
    }
}
