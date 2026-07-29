<?php

namespace App\Domains\Discord\Public\Api;

use App\Domains\Discord\Private\Services\DiscordAuthService;

class DiscordPublicApi
{
    public function __construct(
        private readonly DiscordAuthService $service,
    ) {}

    public function isLinked(int $userId): bool
    {
        return $this->service->getDiscordByUserId($userId) !== null;
    }
}
