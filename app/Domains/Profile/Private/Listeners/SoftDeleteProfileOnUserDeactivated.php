<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Profile\Private\Services\ProfileService;

class SoftDeleteProfileOnUserDeactivated
{
    public function __construct(
        private readonly ProfileService $profiles,
    ) {}

    public function handle(UserDeactivated $event): void
    {
        $this->profiles->softDeleteProfileByUserId($event->userId);
    }
}
