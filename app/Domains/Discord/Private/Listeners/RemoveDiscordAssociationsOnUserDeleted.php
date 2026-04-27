<?php

namespace App\Domains\Discord\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Discord\Private\Services\DiscordAuthService;

class RemoveDiscordAssociationsOnUserDeleted
{
    public function __construct(
        private readonly DiscordAuthService $authService,
        private readonly DiscordPendingNotificationRepository $pendingRepository,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $this->pendingRepository->deleteRecipientsForUser($event->userId);
        $this->authService->deleteUserId($event->userId);
    }
}
