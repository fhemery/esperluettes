<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Story\Private\Services\StoryService;

class SoftDeleteStoriesOnUserDeactivated
{
    public function __construct(
        private readonly StoryService $stories,
    ) {}

    public function handle(UserDeactivated $event): void
    {
        $this->stories->softDeleteStoriesByAuthor($event->userId);
    }
}
