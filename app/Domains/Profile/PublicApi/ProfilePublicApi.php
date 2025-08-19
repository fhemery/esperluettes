<?php

namespace App\Domains\Profile\PublicApi;

use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Profile\Services\ProfileService;
use App\Domains\Profile\Services\ProfileAvatarUrlService;

class ProfilePublicApi implements ProfilePublicApiContract
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly ProfileAvatarUrlService $avatars,
    )
    {
    }
    public function getPublicProfile(int $userId): ?ProfileDto
    {
        $results = $this->getPublicProfiles([$userId]);
        return $results[$userId] ?? null;
    }

    public function getPublicProfiles(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return [];
        }

        // Delegate to service (handles caching and DB fetches)
        $profiles = $this->profiles->getProfilesByUserIds($userIds);

        // Map to DTOs per contract
        $dtos = [];
        foreach ($userIds as $id) {
            $profile = $profiles[$id] ?? null;
            $dtos[$id] = $profile ? new ProfileDto(
                user_id: $profile->user_id,
                display_name: (string) ($profile->display_name ?? ''),
                slug: (string) ($profile->slug ?? ''),
                avatar_url: $this->avatars->publicUrl($profile->profile_picture_path, $profile->user_id),
            ) : null;
        }

        return $dtos;
    }

    public function searchDisplayNames(string $query, int $limit = 50): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $perPage = max(1, (int) $limit);
        $page = $this->profiles->listProfiles(search: $q, page: 1, perPage: $perPage);
        return collect($page->items())->pluck('display_name', 'user_id')->toArray();
    }
}
