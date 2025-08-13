<?php

namespace App\Domains\Profile\Listeners;

use App\Domains\Auth\Events\UserNameUpdated;
use App\Domains\Profile\Services\ProfileService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SyncProfileNameAndSlug implements ShouldHandleEventsAfterCommit
{
    public function __construct(private ProfileService $profiles)
    {
    }

    public function handle(UserNameUpdated $event): void
    {
        $this->profiles->syncNameAndSlugForUser($event->userId, $event->newName);
    }
}
