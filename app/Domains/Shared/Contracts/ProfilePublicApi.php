<?php

namespace App\Domains\Shared\Contracts;

use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Shared\Dto\FullProfileDto;

interface ProfilePublicApi
{
    /**
     * Get the full profile for a given user with fields needed across modules.
     */
    public function getFullProfile(int $userId): ?FullProfileDto;

    /**
     * Get a public profile DTO for a given user ID.
     */
    public function getPublicProfile(int $userId): ?ProfileDto;

    /**
     * Get a public profile DTO for a given profile slug.
     */
    public function getPublicProfileBySlug(string $slug): ?ProfileDto;

    /**
     * Batch get public profile DTOs keyed by user ID.
     * Returns [userId => ProfileDto].
     */
    public function getPublicProfiles(array $userIds): array;

    /**
     * Search profiles by display name and return an associative array of
     * [user_id => display_name]. Implementations may apply caching and limits.
     */
    public function searchDisplayNames(string $query, int $limit = 50): array;

    /**
     * Search public profiles for the global search feature.
     * Returns an array with keys:
     *  - items: ProfileSearchResultDto[] (max 25 items)
     *  - total: int (total matches, uncapped)
     */
    public function searchPublicProfiles(string $query, int $limit = 25): array;
}
