<?php

namespace App\Domains\Story\ViewModels;

use App\Domains\Story\Models\Story;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\ProfileDto;

class StoryShowViewModel
{
    public readonly Story $story;
    public readonly ?int $currentUserId;
    public readonly array $authors;

    public function __construct(
        Story $story,
        ?int $currentUserId,
        private readonly ProfilePublicApi $profileApi
    ) {
        $this->story = $story;
        $this->authors = $this->loadAuthors();
        $this->currentUserId = $currentUserId;
    }

    /**
     * Load author ProfileDtos for the story
     * 
     * @return ProfileDto[]
     */
    private function loadAuthors(): array
    {
        // Get author user IDs from collaborators
        $authorUserIds = $this->story->authors->pluck('user_id')->toArray();
        
        if (empty($authorUserIds)) {
            return [];
        }

        // Fetch profiles via ProfilePublicApi
        $profilesById = $this->profileApi->getPublicProfiles($authorUserIds);
        
        // Return array of ProfileDto objects
        return array_values($profilesById);
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
}
