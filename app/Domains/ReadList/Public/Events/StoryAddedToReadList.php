<?php

namespace App\Domains\ReadList\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class StoryAddedToReadList implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $storyId,
    ) {}

    public static function name(): string { return 'ReadList.Added'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'storyId' => $this->storyId,
        ];
    }

    public function summary(): string
    {
        return trans('readlist::events.added.summary', [
            'userId' => $this->userId,
            'storyId' => $this->storyId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            storyId: (int) ($payload['storyId'] ?? 0),
        );
    }
}
