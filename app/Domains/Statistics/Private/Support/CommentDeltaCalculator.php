<?php

namespace App\Domains\Statistics\Private\Support;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Events\Public\Contracts\DomainEvent;

final class CommentDeltaCalculator
{
    /**
     * @return array<mixed, float|int>|null
     */
    public function forTotalComments(DomainEvent $event): ?array
    {
        if ($event instanceof CommentPosted) {
            return [null => 1];
        }

        return null;
    }

    /**
     * @return array<mixed, float|int>|null
     */
    public function forRootComments(DomainEvent $event): ?array
    {
        if ($event instanceof CommentPosted && ! $event->comment->isReply) {
            return [null => 1];
        }

        return null;
    }
}
