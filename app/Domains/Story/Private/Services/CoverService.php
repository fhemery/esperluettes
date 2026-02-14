<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Story\Private\Models\Story;

class CoverService
{
    /**
     * Get the cover URL (small/standard version) for a story.
     */
    public function getCoverUrl(Story $story): string
    {
        return match ($story->cover_type) {
            Story::COVER_THEMED => $this->themedCoverUrl($story->cover_data),
            default => asset('images/story/default-cover.svg'),
        };
    }

    /**
     * Get the HD cover URL for a story. Returns null when no HD version exists (e.g. default SVG).
     */
    public function getCoverHdUrl(Story $story): ?string
    {
        return match ($story->cover_type) {
            Story::COVER_THEMED => $this->themedCoverHdUrl($story->cover_data),
            default => null,
        };
    }

    /**
     * Whether this cover type supports a lightbox (HD zoom on click).
     */
    public function isClickable(Story $story): bool
    {
        return match ($story->cover_type) {
            Story::COVER_DEFAULT => false,
            default => true,
        };
    }

    /**
     * Get the themed cover URL for a given genre slug.
     */
    public function themedCoverUrl(?string $genreSlug): string
    {
        if (!$genreSlug) {
            return asset('images/story/default-cover.svg');
        }
        return asset("images/story/{$genreSlug}.jpg");
    }

    /**
     * Get the themed HD cover URL for a given genre slug.
     */
    public function themedCoverHdUrl(?string $genreSlug): ?string
    {
        if (!$genreSlug) {
            return null;
        }
        return asset("images/story/{$genreSlug}-hd.jpg");
    }
}
