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
        public readonly string $coverType = 'default',
        public readonly ?string $coverUrl = null,
        public readonly int $totalWordCount = 0,
        public readonly int $totalChaptersCount = 0,
        public readonly int $readChaptersCount = 0,
        public readonly int $progressPercent = 0,
        public readonly ?string $keepReadingUrl = null,
        public readonly ?\DateTime $lastModified = null,
        public readonly bool $isComplete = false,
    ) {}

    public function hasChapters(): bool
    {
        return $this->totalChaptersCount > 0;
    }

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
        
        // Compute last modified date: use lastChapterPublishedAt if exists, otherwise createdAt
        $lastModified = $dto->lastChapterPublishedAt ?? $dto->createdAt;

        $isComplete = (bool) ($dto->isComplete ?? false);
        
        return new self(
            id: (int) $dto->id,
            title: (string) $dto->title,
            slug: (string) $dto->slug,
            description: $dto->description ?? null,
            twDisclosure: (string) $dto->twDisclosure,
            authors: is_array($dto->authors) ? $dto->authors : [],
            genreNames: $genreNames,
            triggerWarningNames: $twNames,
            coverType: (string) ($dto->coverType ?? 'default'),
            coverUrl: $dto->coverUrl,
            totalWordCount: $words,
            totalChaptersCount: $total,
            readChaptersCount: $read,
            progressPercent: $percent,
            keepReadingUrl: $keepReadingUrl,
            lastModified: $lastModified,
            isComplete: $isComplete,
        );
    }
}
