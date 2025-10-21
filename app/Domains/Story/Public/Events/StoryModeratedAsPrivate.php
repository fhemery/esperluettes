<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class StoryModeratedAsPrivate implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'Story.ModeratedAsPrivate'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'title' => $this->title,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.story_moderated_as_private.summary', [
            'id' => $this->storyId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            storyId: (int) ($payload['storyId'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
        );
    }
}
