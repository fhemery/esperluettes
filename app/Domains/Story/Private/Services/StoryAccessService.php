<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Support\GetStoryOptions;

class StoryAccessService
{
    public function __construct(
        private readonly StoryService $storyService,
        private readonly ProfilePublicApi $profiles,
        private readonly AuthPublicApi $authApi,
    ) {}

    /**
     * Batch form of StoryPolicy::view: ids of the given stories (not soft-deleted)
     * the user may read. Staff → all; public → all; community → confirmed;
     * private → collaborators. One stories query.
     *
     * @param array<int,int> $storyIds
     * @return array<int,int>
     */
    public function filterReadableStoryIds(?int $userId, array $storyIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $storyIds)));
        if (empty($ids)) {
            return [];
        }

        $query = Story::query()->whereIn('id', $ids);

        if ($userId !== null) {
            $roles = array_map(
                fn ($r) => $r->slug,
                $this->authApi->getRolesByUserIds([$userId])[$userId] ?? []
            );
            if (array_intersect($roles, [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN])) {
                return $query->pluck('id')->map(fn ($v) => (int) $v)->all();
            }
            $confirmed = in_array(Roles::USER_CONFIRMED, $roles, true);
        } else {
            $confirmed = false;
        }

        $query->where(function ($q) use ($userId, $confirmed) {
            $q->where('visibility', Story::VIS_PUBLIC);
            if ($confirmed) {
                $q->orWhere('visibility', Story::VIS_COMMUNITY);
            }
            if ($userId !== null) {
                $q->orWhere(function ($p) use ($userId) {
                    $p->where('visibility', Story::VIS_PRIVATE)
                        ->whereExists(function ($sub) use ($userId) {
                            $sub->selectRaw('1')
                                ->from('story_collaborators')
                                ->whereColumn('story_collaborators.story_id', 'stories.id')
                                ->where('story_collaborators.user_id', $userId);
                        });
                });
            }
        });

        return $query->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    public function filterUsersWithAccessToStory(array $userIds, int $storyId): array
    {
        return $this->filterUsersWithAccess($userIds, $storyId, null);
    }

    /**
     * Same rules as filterUsersWithAccessToStory, but compute for a provided visibility string.
     * Authors (collaborators) always have access. Returns [] if story does not exist or no users provided.
     *
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    public function filterUsersWithAccessToStoryForVisibility(array $userIds, int $storyId, string $visibility): array
    {
        return $this->filterUsersWithAccess($userIds, $storyId, $visibility);
    }

    /**
     * Centralized access logic. If $visibilityOverride is null, uses story current visibility.
     * Authors/collaborators always have access. Returns [] if story or users not found.
     *
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    private function filterUsersWithAccess(array $userIds, int $storyId, ?string $visibilityOverride): array
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($ids)) {
            return [];
        }

        $story = $this->storyService->getStoryById($storyId, new GetStoryOptions(includeCollaborators: true));
        if (!$story) {
            return [];
        }

        // Filter to existing users via ProfilePublicApi
        $profiles = $this->profiles->getPublicProfiles($ids); // [id => ProfileDto|null]
        $existingIds = [];
        foreach ($ids as $id) {
            if (($profiles[$id] ?? null) !== null) {
                $existingIds[] = (int) $id;
            }
        }
        if (empty($existingIds)) {
            return [];
        }

        // Authors always allowed
        $collaboratorsIds = array_values(array_map('intval', $story->collaborators->pluck('user_id')->all()));
        $collaboratorsInInput = array_values(array_intersect($existingIds, $collaboratorsIds));

        $vis = $visibilityOverride !== null ? (string) $visibilityOverride : (string) $story->visibility;
        if ($vis === 'private') {
            return $collaboratorsInInput;
        }

        if ($vis === 'public') {
            return $existingIds;
        }

        if ($vis === 'community') {
            $rolesByUser = $this->authApi->getRolesByUserIds($existingIds); // [id => RoleDto[]]
            $confirmedIds = [];
            foreach ($rolesByUser as $uid => $roleDtos) {
                foreach ($roleDtos as $rd) {
                    if ($rd->slug === Roles::USER_CONFIRMED) {
                        $confirmedIds[] = (int) $uid;
                        break;
                    }
                }
            }
            return array_values(array_unique(array_merge($collaboratorsInInput, $confirmedIds)));
        }

        // Fallback: conservative
        return $collaboratorsInInput;
    }
}
