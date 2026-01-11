<?php

namespace App\Domains\Profile\Private\Api;

use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;
use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Shared\Dto\FullProfileDto;
use App\Domains\Shared\Dto\ProfileSearchResultDto;
use App\Domains\Profile\Private\Services\ProfileService;
use App\Domains\Profile\Private\Services\ProfileAvatarUrlService;
use App\Domains\Profile\Private\Services\ProfilePrivacyService;
use App\Domains\Auth\Public\Api\AuthPublicApi;

class ProfileApi implements ProfilePublicApiContract
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly ProfileAvatarUrlService $avatars,
        private readonly AuthPublicApi $authApi,
        private readonly ProfilePrivacyService $privacy,
    )
    {
    }

    public function getFullProfile(int $userId): ?FullProfileDto
    {
        $userId = (int) $userId;

        // Fetch profile and user
        $profiles = $this->profiles->getProfilesByUserIds([$userId]);
        $profile = $profiles[$userId] ?? null;

        if (!$profile) {
            return null;
        }

        // Avatar URL from existing service
        $avatarUrl = $this->avatars->publicUrl($profile->profile_picture_path, $userId);

        // Join date in ISO for frontend DateUtils
        $joinIso = optional($profile->created_at)?->toISOString() ?? '';

        // Localized role label (FR)
        $roles = $this->authApi->getRolesByUserIds([$profile->user_id])[$profile->user_id] ?? [];

        return new FullProfileDto(
            userId: (int) $userId,
            displayName: (string) ($profile->display_name ?? ''),
            slug: (string) ($profile->slug ?? ''),
            avatarUrl: $avatarUrl,
            joinDateIso: $joinIso,
            roles: $roles,
        );
    }

    public function getPublicProfile(int $userId): ?ProfileDto
    {
        $results = $this->getPublicProfiles([$userId]);
        return $results[$userId] ?? null;
    }

    public function getPublicProfileBySlug(string $slug): ?ProfileDto
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $profile = $this->profiles->getProfileBySlug($slug);
        if (!$profile) {
            return null;
        }

        return new ProfileDto(
            user_id: $profile->user_id,
            display_name: (string) ($profile->display_name ?? ''),
            slug: (string) ($profile->slug ?? ''),
            avatar_url: $this->avatars->publicUrl($profile->profile_picture_path, $profile->user_id),
        );
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

    public function searchDisplayNames(string $query, int $limit = 50, bool $includeInactive = false): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $perPage = max(1, (int) $limit);
        $page = $this->profiles->listProfiles(search: $q, page: 1, perPage: $perPage, includeInactive: $includeInactive);
        return collect($page->items())->pluck('display_name', 'user_id')->toArray();
    }

    public function searchPublicProfiles(string $query, int $limit = 25, bool $includeInactive = false): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['items' => [], 'total' => 0];
        }
        $cap = max(1, min(25, (int) $limit));
        $page = $this->profiles->listProfiles(search: $q, page: 1, perPage: $cap, includeInactive: $includeInactive);

        $items = [];
        foreach ($page->items() as $p) {
            $items[] = new ProfileSearchResultDto(
                user_id: (int) $p->user_id,
                display_name: (string) ($p->display_name ?? ''),
                slug: (string) ($p->slug ?? ''),
                avatar_url: $this->avatars->publicUrl($p->profile_picture_path, (int) $p->user_id),
                url: route('profile.show', ['profile' => $p->slug])
            );
        }

        return [
            'items' => $items,
            'total' => (int) $page->total(),
        ];
    }

    public function canViewComments(int $profileUserId, ?int $viewerUserId = null): bool
    {
        return $this->privacy->canViewComments($profileUserId, $viewerUserId);
    }
}
