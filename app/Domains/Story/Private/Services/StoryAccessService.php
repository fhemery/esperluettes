<?php

namespace App\Domains\Story\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use App\Domains\Story\Private\Support\GetStoryOptions;

class StoryAccessService
{
    public function __construct(
        private readonly StoryService $storyService,
        private readonly ProfilePublicApi $profiles,
        private readonly AuthPublicApi $authApi,
    ) {}

    /**
     * @param array<int,int> $userIds
     * @return array<int,int>
     */
    public function filterUsersWithAccessToStory(array $userIds, int $storyId): array
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

        $visibility = (string) $story->visibility;
        if ($visibility === 'private') {
            return $collaboratorsInInput;
        }

        if ($visibility === 'public') {
            return $existingIds;
        }

        if ($visibility === 'community') {
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
