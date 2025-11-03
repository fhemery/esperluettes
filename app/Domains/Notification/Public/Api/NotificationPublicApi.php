<?php

namespace App\Domains\Notification\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Validation\ValidationException;

class NotificationPublicApi
{
    public function __construct(
        private ProfilePublicApi $profiles,
        private NotificationService $service,
        private AuthPublicApi $authApi,
    )
    {
    }

    /**
     * Create notification for specific users (validation only for this slice).
     *
     * @param int[] $userIds
     * @throws ValidationException
     */
    public function createNotification(
        array $userIds,
        string $contentKey,
        array $contentData,
        ?int $sourceUserId = null
    ): void {
        // contentKey required, non-empty after trim
        if (trim($contentKey) === '') {
            throw ValidationException::withMessages([
                'contentKey' => [trans('notifications::validation.content_key_required')],
            ]);
        }

        // userIds cannot be empty
        if (empty($userIds)) {
            throw ValidationException::withMessages([
                'userIds' => [trans('notifications::validation.empty_user_ids')],
            ]);
        }

        // Deduplicate & normalize
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        // Validate target users existence
        // Note: getPublicProfiles returns an array keyed by requested IDs with ProfileDto or null
        $profiles = $this->profiles->getPublicProfiles($userIds); // [id => ProfileDto|null]
        $foundIds = [];
        foreach ($profiles as $id => $dto) {
            if ($dto !== null) {
                $foundIds[] = (int) $id;
            }
        }
        $missing = array_values(array_diff($userIds, $foundIds));
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'userIds' => [trans('notifications::validation.non_existing_users')],
            ]);
        }

        // Validate source user if provided
        if ($sourceUserId !== null) {
            $source = $this->profiles->getPublicProfile($sourceUserId);
            if ($source === null) {
                throw ValidationException::withMessages([
                    'sourceUserId' => [trans('notifications::validation.invalid_source_user')],
                ]);
            }
        }

        // Delegate to persistence service
        $this->service->createNotification($userIds, $contentKey, $contentData, $sourceUserId);
    }

    /**
     * Helper for tests/external usage. Stubbed to 0 for this slice.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->service->getUnreadCount($userId);
    }

    /**
     * Broadcast a notification to all eligible users (roles: user, user-confirmed).
     *
     * @throws ValidationException
     */
    public function createBroadcastNotification(
        string $contentKey,
        array $contentData,
        ?int $sourceUserId = null
    ): void {
        if (trim($contentKey) === '') {
            throw ValidationException::withMessages([
                'contentKey' => [trans('notifications::validation.content_key_required')],
            ]);
        }

        if ($sourceUserId !== null && $this->profiles->getPublicProfile($sourceUserId) === null) {
            throw ValidationException::withMessages([
                'sourceUserId' => [trans('notifications::validation.invalid_source_user')],
            ]);
        }

        // Target users with roles USER or USER_CONFIRMED
        $targetIds = $this->authApi->getUserIdsByRoles([Roles::USER, Roles::USER_CONFIRMED], true);
        $targetIds = array_values(array_unique(array_map('intval', $targetIds)));
        if (!empty($targetIds)) {
            $this->service->createNotification($targetIds, $contentKey, $contentData, $sourceUserId);
        }
    }
}
