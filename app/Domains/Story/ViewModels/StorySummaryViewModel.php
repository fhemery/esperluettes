<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;

class StorySummaryViewModel
{
    /** @var ProfileDto[] */
    public readonly array $authors;
    /** @var array<int,string> */
    public readonly array $genreNames;
    /** @var array<int,string> */
    public readonly array $triggerWarningNames;
    public readonly int $readsLoggedTotal;

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        int $readsLoggedTotal = 0,
        array $authors,
        array $genreNames = [],
        array $triggerWarningNames = [],
    ) {
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
        $this->genreNames = array_values(array_filter(array_map('strval', $genreNames)));
        $this->triggerWarningNames = array_values(array_filter(array_map('strval', $triggerWarningNames)));
        $this->readsLoggedTotal = max(0, (int)$readsLoggedTotal);
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

    public function getReadsLoggedTotal(): int
    {
        return $this->readsLoggedTotal;
    }
}
