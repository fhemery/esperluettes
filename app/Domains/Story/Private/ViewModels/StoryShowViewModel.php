<?php

namespace App\Domains\Story\Private\ViewModels;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Story\Private\ViewModels\ChapterSummaryViewModel;
use App\Domains\Shared\ViewModels\RefViewModel;

class StoryShowViewModel
{
    public readonly Story $story;
    public readonly ?int $currentUserId;
    public readonly array $authors;
    /** @var array<int, ChapterSummaryViewModel> */
    public readonly array $chapters;
    public readonly RefViewModel $type;
    public readonly RefViewModel $audience;
    public readonly RefViewModel $copyright;
    public readonly ?RefViewModel $status;
    public readonly ?RefViewModel $feedback;
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
        RefViewModel $type,
        RefViewModel $audience,
        RefViewModel $copyright,
        array $genreNames = [],
        ?RefViewModel $status = null,
        ?RefViewModel $feedback = null,
        array $triggerWarningNames = [],
        string $twDisclosure = Story::TW_UNSPOILED,
    ) {
        $this->story = $story;
        /** @var ProfileDto[] $authors */
        $this->authors = $authors;
        /** @var array<int, ChapterSummaryViewModel> $chapters */
        $this->chapters = $chapters;
        $this->currentUserId = $currentUserId;
        $this->type = $type;
        $this->audience = $audience;
        $this->copyright = $copyright;
        $this->genreNames = array_values(array_filter(array_map('strval', $genreNames)));
        $this->status = $status;
        $this->feedback = $feedback;
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

    // Accessors for referentials
    public function getType(): RefViewModel { return $this->type; }
    public function getAudience(): RefViewModel { return $this->audience; }
    public function getCopyright(): RefViewModel { return $this->copyright; }
    public function getStatus(): ?RefViewModel { return $this->status; }
    public function getFeedback(): ?RefViewModel { return $this->feedback; }

    // Backward-compatible name getters
    public function getTypeName(): string { return $this->type->getName(); }
    public function getAudienceName(): string { return $this->audience->getName(); }
    public function getCopyrightName(): string { return $this->copyright->getName(); }
    public function getStatusName(): ?string { return $this->status?->getName(); }
    public function getFeedbackName(): ?string { return $this->feedback?->getName(); }

    // Description getters for popovers
    public function getTypeDescription(): ?string { return $this->type->getDescription(); }
    public function getAudienceDescription(): ?string { return $this->audience->getDescription(); }
    public function getCopyrightDescription(): ?string { return $this->copyright->getDescription(); }
    public function getStatusDescription(): ?string { return $this->status?->getDescription(); }
    public function getFeedbackDescription(): ?string { return $this->feedback?->getDescription(); }

    

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
