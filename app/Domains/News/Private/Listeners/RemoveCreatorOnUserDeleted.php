<?php

namespace App\Domains\News\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\News\Private\Services\NewsService;

class RemoveCreatorOnUserDeleted
{
    public function __construct(
        private readonly NewsService $news,
    ) {}

    public function handle(UserDeleted $event): void
    {
        // Delegate to domain service
        $this->news->nullifyCreator($event->userId);
    }
}
