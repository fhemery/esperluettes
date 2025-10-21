<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Story\Private\Services\StoryService;

class RestoreStoriesOnUserReactivated
{
    public function __construct(
        private readonly StoryService $stories,
    ) {}

    public function handle(UserReactivated $event): void
    {
        $this->stories->restoreStoriesByAuthor($event->userId);
    }
}
