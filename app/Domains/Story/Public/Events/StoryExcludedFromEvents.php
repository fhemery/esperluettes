<?php

declare(strict_types=1);

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class StoryExcludedFromEvents implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'Story.ExcludedFromEvents'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'story_id' => $this->storyId,
            'title' => $this->title,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.story_excluded_from_events.summary', [
            'id' => $this->storyId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            storyId: (int) ($payload['story_id'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
        );
    }
}
