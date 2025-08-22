<?php

namespace App\Domains\Shared\Contracts;

use App\Domains\Shared\Dto\ProfileDto;

interface ProfilePublicApi
{
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
}
