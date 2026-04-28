<?php

namespace App\Domains\Discord\Private\Listeners;

use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Discord\Public\Events\DiscordDisconnected;

class CleanDiscordNotificationsOnDisconnect
{
    public function __construct(
        private readonly DiscordPendingNotificationRepository $repository,
    ) {}

    public function handle(DiscordDisconnected $event): void
    {
        $this->repository->deleteRecipientsForUser($event->userId);
    }
}
