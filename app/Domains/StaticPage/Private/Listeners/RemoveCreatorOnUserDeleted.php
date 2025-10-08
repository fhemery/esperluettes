<?php

namespace App\Domains\StaticPage\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\StaticPage\Private\Services\StaticPageService;

class RemoveCreatorOnUserDeleted
{
    public function __construct(
        private readonly StaticPageService $pages,
    ) {}

    public function handle(UserDeleted $event): void
    {
        // Delegate to domain service
        $this->pages->nullifyCreator($event->userId);
    }
}
