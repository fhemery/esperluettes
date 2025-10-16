<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class ChapterContentModerated implements DomainEvent
{
    public function __construct(
        public readonly int $storyId,
        public readonly int $chapterId,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'Chapter.ContentModerated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'storyId' => $this->storyId,
            'chapterId' => $this->chapterId,
            'title' => $this->title,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.chapter_content_moderated.summary', [
            'id' => $this->chapterId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            storyId: (int) ($payload['storyId'] ?? 0),
            chapterId: (int) ($payload['chapterId'] ?? 0),
            title: (string) ($payload['title'] ?? ''),
        );
    }
}
