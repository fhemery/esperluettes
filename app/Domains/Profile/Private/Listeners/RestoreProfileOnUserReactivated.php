<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Profile\Private\Services\ProfileService;

class RestoreProfileOnUserReactivated
{
    public function __construct(
        private readonly ProfileService $profiles,
    ) {}

    public function handle(UserReactivated $event): void
    {
        $this->profiles->restoreProfileByUserId($event->userId);
    }
}
