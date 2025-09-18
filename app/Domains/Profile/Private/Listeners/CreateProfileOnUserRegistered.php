<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Profile\Private\Services\ProfileService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CreateProfileOnUserRegistered implements ShouldHandleEventsAfterCommit
{
    public function __construct(private ProfileService $profiles)
    {
    }

    public function handle(UserRegistered $event): void
    {
        $this->profiles->createOrInitProfileOnRegistration($event->userId, $event->displayName);
    }
}
