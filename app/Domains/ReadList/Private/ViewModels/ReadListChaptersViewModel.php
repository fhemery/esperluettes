<?php

namespace App\Domains\ReadList\Private\ViewModels;

use Illuminate\Support\Collection;

class ReadListChaptersViewModel
{
    /** @param Collection<int, ReadListChapterViewModel> $chapters */
    public function __construct(
        public readonly Collection $chapters,
        public readonly string $storySlug,
    ) {}

    public static function fromChapterDtos(array $chapterDtos, string $storySlug): self
    {
        $chapters = collect($chapterDtos)
            ->map(fn($dto) => ReadListChapterViewModel::fromDto($dto, $storySlug))
            ->values();

        return new self(
            chapters: $chapters,
            storySlug: $storySlug,
        );
    }

    public function isEmpty(): bool
    {
        return $this->chapters->isEmpty();
    }

    public function count(): int
    {
        return $this->chapters->count();
    }
}
