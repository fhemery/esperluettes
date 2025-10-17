<?php

namespace App\Domains\Profile\Private\Listeners;

use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Profile\Private\Services\ProfileService;

class CreateProfileOnUserRegistered
{
    public function __construct(private ProfileService $profiles)
    {
    }

    public function handle(UserRegistered $event): void
    {
        $this->profiles->createOrInitProfileOnRegistration($event->userId, $event->displayName);
    }
}
