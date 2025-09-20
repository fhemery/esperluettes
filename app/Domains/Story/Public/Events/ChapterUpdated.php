<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Story\Public\Events\DTO\ChapterSnapshot;

class ChapterUpdated implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly ChapterSnapshot $before,
        public readonly ChapterSnapshot $after,
    ) {}

    public static function name(): string { return 'Chapter.Updated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'before' => $this->before->toPayload(),
            'after' => $this->after->toPayload(),
        ];
    }

    public function summary(): string
    {
        return trans('story::events.chapter_updated.summary', [
            'id' => $this->after->id,
            'title' => $this->after->title,
            'storyId' => $this->storyId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        $beforePayload = (array) ($payload['before'] ?? []);
        $afterPayload = (array) ($payload['after'] ?? []);
        return new static(
            storyId: (int) ($payload['storyId'] ?? 0),
            before: ChapterSnapshot::fromPayload($beforePayload),
            after: ChapterSnapshot::fromPayload($afterPayload),
        );
    }
}
