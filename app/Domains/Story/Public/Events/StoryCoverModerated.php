<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class StoryCoverModerated implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $title,
        public readonly int $storyOwnerId,
    ) {}

    public static function name(): string { return 'Story.CoverModerated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'title' => $this->title,
            'storyOwnerId' => $this->storyOwnerId,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.story_cover_moderated.summary', [
            'id' => $this->storyId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            storyId: (int) ($payload['storyId'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
            storyOwnerId: (int) ($payload['storyOwnerId'] ?? 0),
        );
    }
}
