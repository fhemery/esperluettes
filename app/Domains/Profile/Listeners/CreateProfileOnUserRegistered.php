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
\Illuminate\Support\Facades\Log::debug('Creating profile for newly registered user', [
    'user_id' => $event->userId,
    'name' => $event->name
]);

        $this->profiles->createOrInitProfileOnRegistration($event->userId, $event->name);
    }
}
