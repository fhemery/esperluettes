<?php

namespace App\Domains\Story\Events;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Story\Events\DTO\ChapterSnapshot;

class ChapterUnpublished implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly ChapterSnapshot $chapter,
    ) {}

    public static function name(): string { return 'Chapter.Unpublished'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'chapter' => $this->chapter->toPayload(),
        ];
    }

    public function summary(): string
    {
        return trans('story::events.chapter_unpublished.summary', [
            'id' => $this->chapter->id,
            'title' => $this->chapter->title,
            'storyId' => $this->storyId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $chapterPayload = (array) ($payload['chapter'] ?? []);
        return new static(
            storyId: (int) ($payload['storyId'] ?? 0),
            chapter: ChapterSnapshot::fromPayload($chapterPayload),
        );
    }
}
