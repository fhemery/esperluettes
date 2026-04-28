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
        foreach (array_chunk($userIds, 100) as $userIdChunk) {
            $normalizedChunk = array_map(static fn ($userId): int => (int) $userId, $userIdChunk);
            $discordUsersById = $this->discordAuthService->getDiscordByUserIds(array_values(array_unique($normalizedChunk)));

            foreach ($normalizedChunk as $userId) {
                $discordUser = $discordUsersById->get($userId);
                if ($discordUser === null) {
                    continue;
                }

                $recipients[] = [
                    'user_id'    => $userId,
                    'discord_id' => (string) $discordUser->discord_user_id,
                ];
            }
        }

        if (empty($recipients)) {
            return;
        }

        $pending = $this->repository->createPending($dto->id);
        $this->repository->createRecipients($pending->id, $recipients);
    }
}
