<?php

namespace App\Domains\Comment\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class CommentContentModerated implements DomainEvent
{
    public function __construct(
        public readonly int $commentId,
        public readonly string $entityType,
        public readonly int $entityId,
    ) {}

    public static function name(): string { return 'Comment.ContentModerated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'commentId' => $this->commentId,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
        ];
    }

    public function summary(): string
    {
        return trans('comment::events.comment_content_moderated.summary', [
            'id' => $this->commentId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            commentId: (int)($payload['commentId'] ?? 0),
            entityType: (string)($payload['entityType'] ?? ''),
            entityId: (int)($payload['entityId'] ?? 0),
        );
    }
}
