<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class ModeratorAccessedPrivateStory implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'Story.ModeratorAccessedPrivate'; }

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
        return trans('story::events.moderator_accessed_private_story.summary', [
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
