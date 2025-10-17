<?php

namespace App\Domains\Story\Private\Listeners;

use App\Domains\Comment\Public\Events\CommentDeletedByModeration;
use App\Domains\Story\Private\Services\ChapterCreditService;

class CommentDeletedListener
{
    public function __construct(private ChapterCreditService $credits) {}

    public function handle(CommentDeletedByModeration $event): void
    {
        // Only for root comments on chapters and with a known author
        if ($event->isRoot !== true) {
            return;
        }
        if ($event->entityType !== 'chapter') {
            return;
        }
        if ($event->authorId === null) {
            return;
        }

        $this->credits->revokeOne($event->authorId);
    }
}
