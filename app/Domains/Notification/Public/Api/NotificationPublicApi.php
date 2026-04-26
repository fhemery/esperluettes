<?php

namespace App\Domains\Notification\Public\Api;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Notification\Private\Repositories\NotificationPreferencesRepository;
use App\Domains\Notification\Private\Services\NotificationService;
use App\Domains\Notification\Public\Contracts\NotificationContent;
use App\Domains\Notification\Public\Contracts\NotificationDto;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Validation\ValidationException;

class NotificationPublicApi
{
    public function __construct(
        private ProfilePublicApi $profiles,
        private NotificationService $service,
        private AuthPublicApi $authApi,
        private NotificationFactory $factory,
        private NotificationPreferencesRepository $prefsRepository,
        private NotificationChannelRegistry $channelRegistry,
    ) {}

    /**
     * Create a notification for a list of user IDs with channel-aware delivery.
     *
     * The notification record is always created (even if all users opted out on all channels).
     * Reads are created only for users who have not opted out of the website channel.
     * External channel callbacks are called for the filtered user subset.
     *
     * @param int[] $userIds
     * @throws ValidationException
     */
    public function createNotification(
        array $userIds,
        NotificationContent $content,
        ?int $sourceUserId = null,
        ?\DateTime $createdAt = null
    ): void {
        if (empty($userIds)) {
            throw ValidationException::withMessages([
                'userIds' => [trans('notifications::validation.empty_user_ids')],
            ]);
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        $profiles = $this->profiles->getPublicProfiles($userIds);
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

        if ($sourceUserId !== null && $this->profiles->getPublicProfile($sourceUserId) === null) {
            throw ValidationException::withMessages([
                'sourceUserId' => [trans('notifications::validation.invalid_source_user')],
            ]);
        }

        $type       = $content::type();
        $definition = $this->factory->getTypeDefinition($type);

        $notification = $this->service->createNotificationRecord($content, $sourceUserId, $createdAt);
        $dto = new NotificationDto($notification->id, $type, $content->toData());

        // Website channel (default ON, built-in)
        $websiteUserIds = $definition?->forcedOnWebsite
            ? $userIds
            : $this->prefsRepository->filterForChannel($userIds, $type, 'website', defaultEnabled: true);
        $this->service->createReads($notification->id, $websiteUserIds, $createdAt);

        // Registered external channels
        foreach ($this->channelRegistry->getActiveChannels() as $channel) {
            $channelUserIds = $this->prefsRepository->filterForChannel(
                $userIds, $type, $channel->id, $channel->defaultEnabled
            );
            if (!empty($channelUserIds)) {
                ($channel->deliveryCallback)($dto, $channelUserIds);
            }
        }
    }

    /**
     * Broadcast a notification to all eligible users (roles: user, user-confirmed)
     * with channel-aware delivery.
     *
     * For default-OFF external channels, opted-in users are queried directly to
     * avoid a massive IN clause against the full user list.
     *
     * @throws ValidationException
     */
    public function createBroadcastNotification(
        NotificationContent $content,
        ?int $sourceUserId = null
    ): void {
        if ($sourceUserId !== null && $this->profiles->getPublicProfile($sourceUserId) === null) {
            throw ValidationException::withMessages([
                'sourceUserId' => [trans('notifications::validation.invalid_source_user')],
            ]);
        }

        $type       = $content::type();
        $definition = $this->factory->getTypeDefinition($type);

        $allUserIds = $this->authApi->getUserIdsByRoles([Roles::USER, Roles::USER_CONFIRMED], true);
        $allUserIds = array_values(array_unique(array_map('intval', $allUserIds)));

        $notification = $this->service->createNotificationRecord($content, $sourceUserId);
        $dto = new NotificationDto($notification->id, $type, $content->toData());

        // Website channel (default ON, built-in)
        $websiteUserIds = $definition?->forcedOnWebsite
            ? $allUserIds
            : $this->prefsRepository->filterForChannel($allUserIds, $type, 'website', defaultEnabled: true);
        $this->service->createReads($notification->id, $websiteUserIds);

        // Registered external channels
        foreach ($this->channelRegistry->getActiveChannels() as $channel) {
            if (!$channel->defaultEnabled) {
                // Default-OFF: query opted-in users directly (avoids large IN clause)
                $channelUserIds = $this->prefsRepository->getOptedInUserIds($type, $channel->id);
            } else {
                $channelUserIds = $this->prefsRepository->filterForChannel(
                    $allUserIds, $type, $channel->id, $channel->defaultEnabled
                );
            }
            if (!empty($channelUserIds)) {
                ($channel->deliveryCallback)($dto, $channelUserIds);
            }
        }
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->service->getUnreadCount($userId);
    }

    /**
     * Delete all notifications of a specific type.
     * Returns the number of deleted notifications.
     */
    public function deleteNotificationsByType(string $contentKey): int
    {
        return $this->service->deleteNotificationsByType($contentKey);
    }

    /**
     * Count notifications of a specific type.
     */
    public function countNotificationsByType(string $contentKey): int
    {
        return $this->service->countNotificationsByType($contentKey);
    }
}
