<?php

namespace App\Domains\ReadList\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\ReadList\Private\Services\ReadListService;

class HandleUserDeletedForReadList
{
    public function __construct(private ReadListService $readList)
    {
    }

    public function handle(UserDeleted $event): void
    {
        $this->readList->deleteAllForUser($event->userId);
    }
}
