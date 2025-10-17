<?php

namespace App\Domains\Comment\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Comment\Private\Services\CommentService;

class SoftDeleteCommentsOnUserDeactivated
{
    public function __construct(
        private readonly CommentService $comments,
    ) {}

    public function handle(UserDeactivated $event): void
    {
        $this->comments->softDeleteByAuthor($event->userId);
    }
}
