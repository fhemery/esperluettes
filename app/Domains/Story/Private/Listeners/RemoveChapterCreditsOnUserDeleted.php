<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Story\Private\Services\ChapterCreditService;

class RemoveChapterCreditsOnUserDeleted
{
    public function __construct(
        private readonly ChapterCreditService $credits,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $this->credits->deleteRow($event->userId);
    }
}
