<?php

declare(strict_types=1);

namespace App\Domains\Comment\Events;

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Comment\Events\DTO\CommentSnapshot;

class CommentEdited implements DomainEvent
{
    public function __construct(
        public readonly CommentSnapshot $before,
        public readonly CommentSnapshot $after,
    ) {}

    public static function name(): string { return 'Comment.Edited'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'before' => $this->before->toPayload(),
            'after' => $this->after->toPayload(),
        ];
    }

    public function summary(): string
    {
        return trans('comment::events.comment_edited.summary', [
            'id' => $this->after->commentId,
            'entity' => $this->after->entityType,
            'entity_id' => $this->after->entityId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $before = CommentSnapshot::fromPayload((array)($payload['before'] ?? []));
        $after = CommentSnapshot::fromPayload((array)($payload['after'] ?? []));
        return new static($before, $after);
    }
}
