<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Story\Models\Story;
use App\Domains\Shared\Dto\ProfileDto;

class StoryShowViewModel
{
    public readonly Story $story;
    public readonly ?int $currentUserId;
    public readonly array $authors;
    public readonly ?string $typeName;
    public readonly ?string $audienceName;
    public readonly ?string $copyrightName;

    public function __construct(
        Story $story,
        ?int $currentUserId,
        array $authors,
        ?string $typeName = null,
        ?string $audienceName = null,
        ?string $copyrightName = null,
    ) {
        $this->story = $story;
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
        $this->currentUserId = $currentUserId;
        $this->typeName = $typeName;
        $this->audienceName = $audienceName;
        $this->copyrightName = $copyrightName;
    }

    

    /**
     * Get story title
     */
    public function getTitle(): string
    {
        return $this->story->title;
    }

    /**
     * Get story description (HTML)
     */
    public function getDescription(): string
    {
        return $this->story->description ?? '';
    }

    /**
     * Get story slug
     */
    public function getSlug(): string
    {
        return $this->story->slug;
    }

    /**
     * Get story visibility
     */
    public function getVisibility(): string
    {
        return $this->story->visibility;
    }

    /**
     * Get story creation date
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->story->created_at;
    }

    /**
     * Check if story has description
     */
    public function hasDescription(): bool
    {
        return !empty(trim($this->story->description ?? ''));
    }

    public function isAuthor(): bool
    {
        return collect($this->authors)->pluck('user_id')->contains($this->currentUserId);
    }

    /**
     * Get story type display name
     */
    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    /**
     * Get story audience display name
     */
    public function getAudienceName(): ?string
    {
        return $this->audienceName;
    }

    /**
     * Get story copyright display name
     */
    public function getCopyrightName(): ?string
    {
        return $this->copyrightName;
    }
}
