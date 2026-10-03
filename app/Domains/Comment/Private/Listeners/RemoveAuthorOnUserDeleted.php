<?php

namespace App\Domains\Comment\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Comment\Private\Services\AnnotationService;
use App\Domains\Comment\Private\Services\CommentService;

class RemoveAuthorOnUserDeleted
{
    public function __construct(
        private readonly CommentService $comments,
        private readonly AnnotationService $annotations,
    ) {}

    public function handle(UserDeleted $event): void
    {
        // Delegate DB update to the domain service
        $this->comments->nullifyAuthor($event->userId);
        $this->annotations->nullifyAuthor($event->userId);
    }
}
