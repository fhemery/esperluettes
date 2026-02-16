<?php

namespace App\Domains\Story\Private\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\CoverService;

class StorySummaryViewModel
{
    /** @var ProfileDto[] */
    public readonly array $authors;
    /** @var array<int,string> */
    public readonly array $genreNames;
    /** @var array<int,string> */
    public readonly array $triggerWarningNames;
    public readonly int $readsLoggedTotal;
    public readonly int $chaptersCount;
    public readonly int $wordsTotal;
    public readonly string $twDisclosure;
    public readonly string $coverType;
    public readonly string $coverUrl;

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        int $readsLoggedTotal = 0,
        int $chaptersCount = 0,
        int $wordsTotal = 0,
        array $authors,
        array $genreNames = [],
        array $triggerWarningNames = [],
        string $twDisclosure = Story::TW_UNSPOILED,
        string $coverType = Story::COVER_DEFAULT,
        string $coverUrl = '',
    ) {
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
        $this->genreNames = array_values(array_filter(array_map('strval', $genreNames)));
        $this->triggerWarningNames = array_values(array_filter(array_map('strval', $triggerWarningNames)));
        $this->readsLoggedTotal = max(0, (int)$readsLoggedTotal);
        $this->chaptersCount = max(0, (int)$chaptersCount);
        $this->wordsTotal = max(0, (int)$wordsTotal);
        $this->twDisclosure = (string)$twDisclosure;
        $this->coverType = $coverType;
        $this->coverUrl = $coverUrl;
    }

    public static function fromStory(Story $story, array $genresRef, array $triggerWarningRef, CoverService $coverService): self
    {
        return new self(
            $story->id,
            $story->title,
            $story->slug,
            $story->description,
            $story->reads_logged_total,
            $story->chapters_count,
            $story->words_total,
            $story->authors,
            $story->genre_names,
            $story->trigger_warning_names,
            $story->twDisclosure,
            coverType: (string) ($story->cover_type ?? Story::COVER_DEFAULT),
            coverUrl: $coverService->getCoverUrl($story),
        );
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return (string) ($this->description ?? '');
    }

    /**
     * @return ProfileDto[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getFirstAuthorName(): ?string
    {
        return $this->authors[0]->display_name ?? null;
    }

    /**
     * @return array<int,string>
     */
    public function getGenreNames(): array
    {
        return $this->genreNames;
    }

    /**
     * @return array<int,string>
     */
    public function getTriggerWarningNames(): array
    {
        return $this->triggerWarningNames;
    }

    public function getTwDisclosure(): string
    {
        return $this->twDisclosure;
    }

    public function getReadsLoggedTotal(): int
    {
        return $this->readsLoggedTotal;
    }

    public function getChaptersCount(): int
    {
        return $this->chaptersCount;
    }

    public function getWordsTotal(): int
    {
        return $this->wordsTotal;
    }
}
