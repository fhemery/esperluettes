<?php

namespace App\Domains\Fanart\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class FanartDeletedByModeration implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $fanartId,
        public readonly int $storyId,
        public readonly int $userId,
        public readonly int $moderatorId,
    ) {
    }

    public static function name(): string
    {
        return 'Fanart.DeletedByModeration';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'fanartId' => $this->fanartId,
            'storyId' => $this->storyId,
            'userId' => $this->userId,
            'moderatorId' => $this->moderatorId,
        ];
    }

    public function summary(): string
    {
        return trans('fanart::events.fanart_deleted_by_moderation.summary', [
            'id' => $this->fanartId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            fanartId: (int) ($payload['fanartId'] ?? 0),
            storyId: (int) ($payload['storyId'] ?? 0),
            userId: (int) ($payload['userId'] ?? 0),
            moderatorId: (int) ($payload['moderatorId'] ?? 0),
        );
    }
}
