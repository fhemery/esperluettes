<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Shared\Dto\ProfileDto;

class StorySummaryViewModel
{
    /** @var ProfileDto[] */
    public readonly array $authors;

    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $description,
        array $authors
    ) {
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
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
}
