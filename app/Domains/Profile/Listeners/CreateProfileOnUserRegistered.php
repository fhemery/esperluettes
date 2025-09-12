<?php

namespace App\Domains\Profile\Listeners;

use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Profile\Services\ProfileService;
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
