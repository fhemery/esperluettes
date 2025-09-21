<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;

class ChapterDeleted implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly ChapterSnapshot $chapter,
    ) {}

    public static function name(): string { return 'Chapter.Deleted'; }

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
        return trans('story::events.chapter_deleted.summary', [
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
