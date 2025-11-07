<?php

namespace App\Domains\Story\Public\Api;

use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Shared\Dto\StorySearchResultDto;
use App\Domains\Story\Private\Services\StorySearchService;
use App\Domains\Story\Private\Services\StoryService;
use App\Domains\Story\Private\Services\StoryAccessService;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;
use App\Domains\Story\Private\Support\StoryFilterAndPagination;
use App\Domains\Story\Public\Contracts\StorySummaryDto;
use App\Domains\Story\Public\Contracts\UserStoryListItemDto;
use App\Domains\Story\Public\Contracts\StoryQueryFilterDto;
use App\Domains\Story\Public\Contracts\StoryQueryPaginationDto;
use App\Domains\Story\Public\Contracts\StoryQueryFieldsToReturnDto;
use App\Domains\Story\Public\Contracts\PaginatedStoryDto;
use App\Domains\StoryRef\Private\Services\StoryRefLookupService;

class StoryMapperHelper {
    /**
     * @var StoryRefGenre[] $genres
     * @var StoryRefTriggerWarning[] $triggerWarnings
     * @var ProfileDto[] $profiles
     */
    public function __construct(
        public readonly array $genres,
        public readonly array $triggerWarnings,
        public readonly array $profiles,
    ) {
    }
}

class StoryPublicApi
{
    public function __construct(
        private readonly ProfilePublicApi $profiles,
        private readonly StorySearchService $search,
        private readonly StoryService $storyService,
        private readonly StoryAccessService $accessService,
        private readonly StoryRefLookupService $storyRefService
    ) {
    }

    /**
     * Return a list of stories matching the given filter and pagination.
     */
    public function listStories(
        StoryQueryFilterDto $filter = new StoryQueryFilterDto(), 
        StoryQueryPaginationDto $pagination = new StoryQueryPaginationDto(page: 1, pageSize: 10), 
        StoryQueryFieldsToReturnDto $fieldsToReturn = new StoryQueryFieldsToReturnDto()
    ): PaginatedStoryDto
    {
        $page = max(1, (int) $pagination->page);
        $perPage = max(1, (int) $pagination->pageSize);


        $fp = new StoryFilterAndPagination(
            page: $page,
            perPage: $perPage,
            visibilities: $filter->visibilities,
            genreIds: $filter->genreIds,
            requirePublishedChapter: false,
            onlyStoryIds: $filter->onlyStoryIds,
            readStatus: $filter->readStatus,
        );

        $options = new GetStoryOptions(
            includeAuthors: $fieldsToReturn->includeAuthors,
            includeGenreIds: $fieldsToReturn->includeGenreIds,
            includeTriggerWarningIds: $fieldsToReturn->includeTriggerWarningIds,
            includeChapters: $fieldsToReturn->includeChapters,
            includeReadingProgress: $fieldsToReturn->includeReadingProgress
        );

        $paginator = $this->storyService->searchStories($fp, $options);
        
        $profiles = [];
        if ($fieldsToReturn->includeAuthors) {
            $authorIds = collect($paginator->items())
                ->flatMap(fn($s) => $s->authors->pluck('user_id'))
                ->unique()
                ->values()
                ->all();
            $profiles = $this->profiles->getPublicProfiles($authorIds);
        }
        $helper = new StoryMapperHelper(
            genres: $fieldsToReturn->includeGenreIds ? $this->storyRefService->getGenres()->all() : [],
            triggerWarnings: $fieldsToReturn->includeTriggerWarningIds ? $this->storyRefService->getTriggerWarnings()->all() : [],
            profiles: $profiles,
        );

        return PaginatedStoryDto::from($paginator, $fieldsToReturn, $helper);
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

    public function getStory(int $storyId): ?StorySummaryDto
    {
        $story = $this->storyService->getStoryById($storyId, new GetStoryOptions(includeChapters: true));
        return $story ? StorySummaryDto::fromModel($story) : null;
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

    /**
     * Filter a list of user IDs to those who currently have access to the given story.
     * This method never expands beyond the provided list. It deduplicates and filters
     * out non-existing users, then applies Story visibility rules.
     *
     * Rules:
     * - public: all existing users
     * - private: authors only
     * - community: authors + users with role user-confirmed
     *
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    public function filterUsersWithAccessToStory(array $userIds, int $storyId): array
    {
        return $this->accessService->filterUsersWithAccessToStory($userIds, $storyId);
    }

    /**
     * Compute which users gained or lost access after a visibility change.
     * The comparison is restricted to the provided userIds.
     *
     * Returns ['gained' => int[], 'lost' => int[]]. If story does not exist,
     * or previous visibility equals current, both lists are empty.
     *
     * @param array<int,int> $userIds
     * @return array{gained: array<int,int>, lost: array<int,int>}
     */
    public function diffAccessForUsers(array $userIds, int $storyId, string $previousVisibility): array
    {
        $current = $this->accessService->filterUsersWithAccessToStory($userIds, $storyId);
        $before = $this->accessService->filterUsersWithAccessToStoryForVisibility($userIds, $storyId, $previousVisibility);

        // If either indicates story missing or vis unchanged, service will handle; we just diff
        $setCurrent = array_values(array_unique(array_map('intval', $current)));
        $setBefore = array_values(array_unique(array_map('intval', $before)));

        // If previous equals current visibility, underlying service will return identical sets; diff yields empty
        $gained = array_values(array_diff($setCurrent, $setBefore));
        $lost = array_values(array_diff($setBefore, $setCurrent));

        sort($gained);
        sort($lost);

        return ['gained' => $gained, 'lost' => $lost];
    }

}
