<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Story\Private\Services\StoryService;

class RemoveStoriesOnUserDeleted
{
    public function __construct(
        private readonly StoryService $stories,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $this->stories->deleteStoriesByAuthor($event->userId);
    }
}
