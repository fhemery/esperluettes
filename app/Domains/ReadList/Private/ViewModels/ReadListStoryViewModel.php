<?php

namespace App\Domains\ReadList\Private\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Story\Public\Contracts\StoryDto;

class ReadListStoryViewModel
{
    /** @param ProfileDto[] $authors */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $twDisclosure,
        public readonly array $authors,
        /** @var array<int,string> */
        public readonly array $genreNames,
        /** @var array<int,string> */
        public readonly array $triggerWarningNames,
        public readonly ?string $coverUrl = '/images/story/default-cover.svg',
        public readonly int $totalWordCount = 0,
        public readonly int $totalChaptersCount = 0,
        public readonly int $readChaptersCount = 0,
        public readonly int $progressPercent = 0,
        public readonly ReadListChaptersViewModel $chapters,
        public readonly ?string $keepReadingUrl = null,
    ) {}

    public static function fromDto(StoryDto $dto): self
    {
        $genreNames = [];
        if (is_array($dto->genres)) {
            foreach ($dto->genres as $g) {
                $name = is_array($g) ? ($g['name'] ?? null) : (property_exists($g, 'name') ? $g->name : null);
                if ($name !== null) {
                    $genreNames[] = (string) $name;
                }
            }
        }
        $twNames = [];
        if (is_array($dto->triggerWarnings)) {
            foreach ($dto->triggerWarnings as $tw) {
                $name = is_array($tw) ? ($tw['name'] ?? null) : (property_exists($tw, 'name') ? $tw->name : null);
                if ($name !== null) {
                    $twNames[] = (string) $name;
                }
            }
        }

        // Progress: compute from chapters if provided
        $chapters = collect($dto->chapters ?? []);
        $total = $chapters->count();
        $read = $chapters->where('isRead', true)->count();
        $words = $chapters->map(function ($c) {
            return (int) $c?->wordCount ?? 0;
        })->sum();
        $percent = $total > 0 ? (int) round(($read / $total) * 100) : 0;
        
        // Create chapters view model
        $chaptersViewModel = ReadListChaptersViewModel::fromChaptersArray($chapters->all(), $dto->slug);
        
        // Calculate keep reading URL
        $keepReadingUrl = null;
        if ($total > 0) {
            $firstUnreadChapter = $chapters->firstWhere('isRead', false);
            if ($firstUnreadChapter) {
                $keepReadingUrl = route('chapters.show', [
                    'storySlug' => $dto->slug,
                    'chapterSlug' => $firstUnreadChapter->slug
                ]);
            }
        }
        
        return new self(
            id: (int) $dto->id,
            title: (string) $dto->title,
            slug: (string) $dto->slug,
            description: $dto->description ?? null,
            twDisclosure: (string) $dto->twDisclosure,
            authors: is_array($dto->authors) ? $dto->authors : [],
            genreNames: $genreNames,
            triggerWarningNames: $twNames,
            coverUrl: '/images/story/default-cover.svg',
            totalWordCount: $words,
            totalChaptersCount: $total,
            readChaptersCount: $read,
            progressPercent: $percent,
            chapters: $chaptersViewModel,
            keepReadingUrl: $keepReadingUrl,
        );
    }
}
