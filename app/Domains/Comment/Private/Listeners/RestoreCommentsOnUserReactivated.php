<?php

namespace App\Domains\Comment\Private\Listeners;

use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Comment\Private\Services\CommentService;

class RestoreCommentsOnUserReactivated
{
    public function __construct(
        private readonly CommentService $comments,
    ) {}

    public function handle(UserReactivated $event): void
    {
        $this->comments->restoreByAuthor($event->userId);
    }
}
