<?php

namespace App\Domains\Story\Listeners;

use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Story\Services\ChapterCreditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class GrantInitialCreditsOnUserRegistered implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly ChapterCreditService $credits,
    ) {}

    public function handle(UserRegistered $event): void
    {
        $this->credits->grantInitialOnRegistration((int)$event->userId);
    }
}
