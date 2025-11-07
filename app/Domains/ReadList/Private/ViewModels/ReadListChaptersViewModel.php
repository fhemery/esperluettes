<?php

namespace App\Domains\ReadList\Private\ViewModels;

use App\Domains\Story\Public\Contracts\StoryChapterDto;

use Illuminate\Support\Collection;

class ReadListChaptersViewModel
{
    /** @param Collection<int, ReadListChapterViewModel> $chapters */
    public function __construct(
        public readonly Collection $chapters,
        public readonly int $chaptersBefore,
        public readonly int $chaptersAfter,
        public readonly bool $isEmpty,
    ) {}

    public static function fromChaptersArray(array $chapters, string $storySlug): self
    {
        if (empty($chapters)) {
            return new self(collect(), 0, 0, true);
        }

        $totalChapters = count($chapters);
        $firstUnreadIndex = null;

        // Find first unread chapter
        foreach ($chapters as $index => $chapter) {
            if (!$chapter->isRead) {
                $firstUnreadIndex = $index;
                break;
            }
        }

        $displayChapters = [];
        $startIndex = 0;

        if ($firstUnreadIndex !== null) {
            // Start from the chapter immediately before first unread
            $startIndex = max(0, $firstUnreadIndex - 1);
        } else {
            // All chapters are read, show last 5
            $startIndex = max(0, $totalChapters - 5);
        }

        // Get up to 5 chapters starting from startIndex
        $displayChapters = array_slice($chapters, $startIndex, 5);

        // Calculate chapters before and after the displayed slice
        $chaptersBefore = $startIndex;
        $chaptersAfter = max(0, $totalChapters - ($startIndex + count($displayChapters)));

        // Convert to view models
        $chapterViewModels = collect($displayChapters)
            ->map(fn($chapter) => ReadListChapterViewModel::fromDto($chapter, $storySlug))
            ->values();

        return new self(
            $chapterViewModels,
            $chaptersBefore,
            $chaptersAfter,
            false
        );
    }
}
