<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Story\Private\Services\ChapterCreditService;
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
