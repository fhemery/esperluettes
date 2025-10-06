<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Profile\Private\Services\ProfileService;

class RemoveProfileOnUserDeleted
{
    public function __construct(
        private readonly ProfileService $profiles,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $this->profiles->deleteProfileByUserId($event->userId);
    }
}
