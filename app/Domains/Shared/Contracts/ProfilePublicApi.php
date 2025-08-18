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
     * Batch get public profile DTOs keyed by user ID.
     * Returns [userId => ProfileDto].
     */
    public function getPublicProfiles(array $userIds): array;
}
