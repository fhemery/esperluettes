<?php

namespace App\Domains\Story\Events;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Story\Events\DTO\StorySnapshot;

class StoryUpdated implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly StorySnapshot $before,
        public readonly StorySnapshot $after,
    ) {}

    public static function name(): string { return 'Story.Updated'; }

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
        return trans('story::events.story_updated.summary', [
            'id' => $this->after->storyId,
            'title' => $this->after->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $before = StorySnapshot::fromPayload((array) ($payload['before'] ?? []));
        $after = StorySnapshot::fromPayload((array) ($payload['after'] ?? []));
        return new static($before, $after);
    }
}

