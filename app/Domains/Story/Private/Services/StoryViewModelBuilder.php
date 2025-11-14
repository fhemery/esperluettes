<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\ViewModels\StorySummaryViewModel;
use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;

class StoryViewModelBuilder
{
    public function __construct(
        private readonly ProfilePublicApi $profileApi,
        private readonly StoryRefPublicApi $storyRefs,
    ) {}

    /**
     * Build StorySummaryViewModel items with authors, genres, trigger warnings,
     * and preloaded aggregates from a list of stories. Reusable by index and profile listings.
     *
     * @param array<int, Story> $stories
     * @return array<int, StorySummaryViewModel>
     */
    public function buildStorySummaryItems(array $stories): array
    {
        // Collect all author user IDs from the list
        $authorIds = [];
        foreach ($stories as $s) {
            foreach ($s->authors as $a) {
                $authorIds[] = (int) $a->user_id;
            }
        }
        $authorIds = array_values(array_unique($authorIds));

        $profilesById = empty($authorIds)
            ? []
            : $this->profileApi->getPublicProfiles($authorIds); // [userId => ProfileDto]

        $items = [];
        $genresById = $this->storyRefs->getAllGenres()->keyBy('id');
        $twById = $this->storyRefs->getAllTriggerWarnings()->keyBy('id');

        foreach ($stories as $story) {
            // Map authors to public profile DTOs
            $authorDtos = [];
            foreach ($story->authors as $author) {
                $dto = $profilesById[$author->user_id] ?? null;
                if ($dto) {
                    $authorDtos[] = $dto;
                }
            }

            // Map genre IDs to names for badges
            $gNames = [];
            $ids = $story->genres?->pluck('id')->all() ?? [];
            foreach ($ids as $gid) {
                $row = $genresById->get($gid);
                if ($row) {
                    $gNames[] = (string) $row->name;
                }
            }

            // Map trigger warning IDs to names for badges
            $twNames = [];
            $tids = $story->triggerWarnings?->pluck('id')->all() ?? [];
            foreach ($tids as $tid) {
                $row = $twById->get($tid);
                if ($row) {
                    $twNames[] = (string) $row->name;
                }
            }

            // Use preloaded aggregates to avoid N+1
            $chaptersCount = (int) ($story->published_chapters_count ?? 0);
            $wordsTotal = (int) ($story->published_words_total ?? 0);

            $items[] = new StorySummaryViewModel(
                id: $story->id,
                title: $story->title,
                slug: $story->slug,
                description: $story->description,
                readsLoggedTotal: (int)($story->reads_logged_total ?? 0),
                chaptersCount: $chaptersCount,
                wordsTotal: $wordsTotal,
                authors: $authorDtos,
                genreNames: $gNames,
                triggerWarningNames: $twNames,
                twDisclosure: (string) $story->tw_disclosure,
            );
        }

        return $items;
    }

    public function buildStorySummaryItem(Story $story): StorySummaryViewModel
    {
        return $this->buildStorySummaryItems([$story])[0];
    }
}
