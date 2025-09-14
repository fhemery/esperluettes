<?php

declare(strict_types=1);

namespace App\Domains\Comment\Events;

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Comment\Events\DTO\CommentSnapshot;

class CommentPosted implements DomainEvent
{
    public function __construct(
        public readonly CommentSnapshot $comment,
    ) {}

    public static function name(): string { return 'Comment.Posted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'comment' => $this->comment->toPayload(),
        ];
    }

    public function summary(): string
    {
        if ($this->comment->isReply) {
            return trans('comment::events.comment_posted.summary_reply', [
                'id' => $this->comment->commentId,
                'entity' => $this->comment->entityType,
                'entity_id' => $this->comment->entityId,
                'parentComment' => $this->comment->parentCommentId,
            ]);
        }
        return trans('comment::events.comment_posted.summary_root', [
            'id' => $this->comment->commentId,
            'entity' => $this->comment->entityType,
            'entity_id' => $this->comment->entityId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $comment = CommentSnapshot::fromPayload((array) ($payload['comment'] ?? []));
        return new static($comment);
    }
}

