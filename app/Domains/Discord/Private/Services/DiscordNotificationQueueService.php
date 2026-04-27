<?php

namespace App\Domains\Discord\Private\Services;

use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Notification\Public\Contracts\NotificationDto;

class DiscordNotificationQueueService
{
    public function __construct(
        private readonly DiscordAuthService $discordAuthService,
        private readonly DiscordPendingNotificationRepository $repository,
    ) {}

    /**
     * Queue a notification for Discord delivery.
     *
     * Resolves each user's discord_id and inserts a pending_notification + recipient rows.
     * The NotificationDto payload is intentionally discarded — content is re-fetched at
     * poll time via NotificationPublicApi::getNotificationsByIds() to avoid storing it twice.
     * Users with no linked Discord account are silently skipped.
     */
    public function queue(NotificationDto $dto, array $userIds): void
    {
        $recipients = [];
        foreach ($userIds as $userId) {
            $discordUser = $this->discordAuthService->getDiscordByUserId((int) $userId);
            if ($discordUser === null) {
                continue;
            }
            $recipients[] = [
                'user_id'    => (int) $userId,
                'discord_id' => (string) $discordUser->discord_user_id,
            ];
        }

        if (empty($recipients)) {
            return;
        }

        $pending = $this->repository->createPending($dto->id);
        $this->repository->createRecipients($pending->id, $recipients);
    }
}
