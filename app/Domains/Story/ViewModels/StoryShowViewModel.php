<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Story\Models\Story;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Story\ViewModels\ChapterSummaryViewModel;

class StoryShowViewModel
{
    public readonly Story $story;
    public readonly ?int $currentUserId;
    public readonly array $authors;
    /** @var array<int, ChapterSummaryViewModel> */
    public readonly array $chapters;
    public readonly string $typeName;
    public readonly string $audienceName;
    public readonly string $copyrightName;
    public readonly ?string $statusName;
    public readonly ?string $feedbackName;
    /** @var array<int,string> */
    public readonly array $genreNames;
    /** @var array<int,string> */
    public readonly array $triggerWarningNames;
    public readonly string $twDisclosure;

    public function __construct(
        Story $story,
        ?int $currentUserId,
        array $authors,
        array $chapters,
        string $typeName,
        string $audienceName,
        string $copyrightName,
        array $genreNames = [],
        ?string $statusName = null,
        ?string $feedbackName = null,
        array $triggerWarningNames = [],
        string $twDisclosure = Story::TW_UNSPOILED,
    ) {
        $this->story = $story;
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
        /** @var array<int, ChapterSummaryViewModel> $chapters */
        $this->chapters = $chapters;
        $this->currentUserId = $currentUserId;
        $this->typeName = (string)$typeName;
        $this->audienceName = (string)$audienceName;
        $this->copyrightName = (string)$copyrightName;
        $this->genreNames = array_values(array_filter(array_map('strval', $genreNames)));
        $this->statusName = $statusName;
        $this->feedbackName = $feedbackName;
        $this->triggerWarningNames = array_values(array_filter(array_map('strval', $triggerWarningNames)));
        $this->twDisclosure = (string)$twDisclosure;
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
    public function getTypeName(): string
    {
        return $this->typeName;
    }

    /**
     * Get story audience display name
     */
    public function getAudienceName(): string
    {
        return $this->audienceName;
    }

    /**
     * Get story copyright display name
     */
    public function getCopyrightName(): string
    {
        return $this->copyrightName;
    }

    /**
     * Get story status display name
     */
    public function getStatusName(): ?string
    {
        return $this->statusName;
    }

    /**
     * Get story feedback display name
     */
    public function getFeedbackName(): ?string
    {
        return $this->feedbackName;
    }

    /**
     * Get genre names for display
     * @return array<int,string>
     */
    public function getGenreNames(): array
    {
        return $this->genreNames;
    }

    /**
     * Get trigger warning names for display
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

    public function hasListedTriggerWarnings(): bool
    {
        return !empty($this->triggerWarningNames);
    }

    public function isNoTw(): bool
    {
        return $this->twDisclosure === Story::TW_NO_TW;
    }

    public function isUnspoiledTw(): bool
    {
        return $this->twDisclosure === Story::TW_UNSPOILED;
    }

    public function getReadsLoggedTotal(): int
    {
        return (int) ($this->story->reads_logged_total ?? 0);
    }

    /**
     * Sum of words across published chapters (computed on the fly)
     */
    public function getWordsTotal(): int
    {
        return (int) $this->story->publishedWordCount();
    }

    /**
     * Sum of characters across published chapters (computed on the fly)
     */
    public function getCharactersTotal(): int
    {
        return (int) $this->story->publishedCharacterCount();
    }
}
