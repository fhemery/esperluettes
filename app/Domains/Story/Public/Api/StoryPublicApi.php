<?php

namespace App\Domains\Story\Public\Api;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\StorySearchResultDto;
use App\Domains\Story\Private\Services\StorySearchService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Public\Contracts\StoryDto;
use App\Domains\Story\Public\Contracts\UserStoryListItemDto;

class StoryPublicApi
{
    public function __construct(
        private readonly ProfilePublicApi $profiles,
        private readonly StorySearchService $search,
        private readonly StoryService $storyService
    ) {
    }

    /**
     * Return a simple list of stories authored by the given user as DTOs.
     * Results ordered by updated_at DESC (then id DESC). Optionally exclude co-authored.
     *
     * @return array<int, \App\Domains\Story\Public\Contracts\UserStoryListItemDto>
     */
    public function getStoriesForUser(int $userId, bool $excludeCoauthored = false): array
    {
        $stories = $this->storyService->getStoriesForUserList($userId, $excludeCoauthored);
        return collect($stories)->map(function (Story $story) {
            return new UserStoryListItemDto(
                id: (int) $story->id,
                title: (string) $story->title,
            );
        })->all();
    }

    public function getStory(int $storyId): ?StoryDto
    {
        $story = $this->storyService->getStoryById($storyId, new GetStoryOptions(includeChapters: true));
        return $story ? StoryDto::fromModel($story) : null;
    }

    public function isAuthor(int $userId, int $storyId): bool
    {
        return in_array($userId, $this->storyService->getAuthorIds($storyId));
    }

    /**
     * Return list of author user IDs for a story.
     * @return int[]
     */
    public function getAuthorIds(int $storyId): array
    {
        return array_values(array_map('intval', $this->storyService->getAuthorIds($storyId)));
    }

    public function countAuthoredStories(int $userId): int
    {
        return $this->storyService->countAuthoredStories($userId);
    }

    public function searchStories(string $query, ?int $viewerUserId = null, int $limit = 25): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['items' => [], 'total' => 0];
        }

        $cap = max(1, min(25, (int) $limit));

        // Delegate to service (enforces visibility and matching)
        $result = $this->search->search($q, $cap);
        $rows = $result['rows'];
        $total = (int) $result['total'];

        // Resolve author display names via ProfilePublicApi
        $authorUserIds = [];
        foreach ($rows as $row) {
            foreach ($row->authors as $a) {
                $authorUserIds[] = (int) $a->user_id;
            }
        }
        $authorUserIds = array_values(array_unique($authorUserIds));
        $profiles = $this->profiles->getPublicProfiles($authorUserIds);

        $items = [];
        foreach ($rows as $row) {
            $authors = [];
            foreach ($row->authors as $a) {
                $dto = $profiles[(int) $a->user_id] ?? null;
                if ($dto) {
                    $authors[] = (string) $dto->display_name;
                }
            }
            $items[] = new StorySearchResultDto(
                id: (int) $row->id,
                title: (string) $row->title,
                slug: (string) $row->slug,
                cover_url: null,
                authors: $authors,
                url: route('stories.show', ['slug' => $row->slug])
            );
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

}
