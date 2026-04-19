<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class ModeratorAccessedPrivateChapter implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $chapterId,
        public readonly string $title,
        public readonly int $storyId,
    ) {}

    public static function name(): string { return 'Story.ModeratorAccessedPrivateChapter'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'chapterId' => $this->chapterId,
            'title' => $this->title,
            'storyId' => $this->storyId,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.moderator_accessed_private_chapter.summary', [
            'id' => $this->chapterId,
            'title' => $this->title,
            'storyId' => $this->storyId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            chapterId: (int) ($payload['chapterId'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
            storyId: (int) ($payload['storyId'] ?? 0),
        );
    }
}
