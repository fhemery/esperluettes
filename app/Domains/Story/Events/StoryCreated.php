<?php

namespace App\Domains\Story\Events;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Story\Events\DTO\StorySnapshot;

class StoryCreated implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly StorySnapshot $story,
    ) {}

    public static function name(): string { return 'Story.Created'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'story' => $this->story->toPayload(),
        ];
    }

    public function summary(): string
    {
        $visLabel = trans('story::shared.visibility.options.' . $this->story->visibility);
        return trans('story::events.story_created.summary', [
            'id' => $this->story->storyId,
            'title' => $this->story->title,
            'visibility' => $visLabel,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $storyPayload = (array) ($payload['story'] ?? []);
        return new static(
            story: StorySnapshot::fromPayload($storyPayload),
        );
    }
}
