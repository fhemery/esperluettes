<?php

namespace App\Domains\Discord\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Discord\Private\Services\DiscordAuthService;

class RemoveDiscordAssociationsOnUserDeleted
{
    public function __construct(
        private readonly DiscordAuthService $authService,
    ) {}

    public function handle(UserDeleted $event): void
    {
        // Remove all Discord associations for this user
        $this->authService->deleteUserId($event->userId);
    }
}
