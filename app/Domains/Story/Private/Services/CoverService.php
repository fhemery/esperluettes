<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Story\Private\Models\Story;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;

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

    /**
     * Return genres that have a themed cover available.
     * Checks both has_cover flag AND file existence on disk.
     *
     * @param int[] $genreIds  Restrict to these genre IDs (e.g. the story's current genres)
     * @return array<int, array{id:int,slug:string,name:string}>
     */
    public function getAvailableThemedCovers(array $genreIds = []): array
    {
        /** @var StoryRefPublicApi $storyRefs */
        $storyRefs = app(StoryRefPublicApi::class);
        $allGenres = $storyRefs->getAllGenres(new StoryRefFilterDto(activeOnly: true));

        return $allGenres
            ->filter(function (GenreDto $g) use ($genreIds) {
                if (!$g->has_cover) {
                    return false;
                }
                if (!empty($genreIds) && !in_array($g->id, $genreIds, false)) {
                    return false;
                }
                return file_exists(public_path("images/story/{$g->slug}.jpg"));
            })
            ->map(fn (GenreDto $g) => ['id' => $g->id, 'slug' => $g->slug, 'name' => $g->name])
            ->values()
            ->all();
    }
}
