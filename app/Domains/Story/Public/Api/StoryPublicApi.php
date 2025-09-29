<?php

namespace App\Domains\Story\Public\Api;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\StorySearchResultDto;
use App\Domains\Story\Private\Services\StorySearchService;
use App\Domains\Story\Private\Services\StoryService;

class StoryPublicApi
{
    public function __construct(
        private readonly ProfilePublicApi $profiles,
        private readonly StorySearchService $search,
        private readonly StoryService $storyService
    ) {
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
