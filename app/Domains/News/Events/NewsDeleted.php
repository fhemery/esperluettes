<?php

namespace App\Domains\News\Events;

use App\Domains\Events\Contracts\DomainEvent;

class NewsDeleted implements DomainEvent
{
    public function __construct(
        public readonly int $newsId,
        public readonly string $slug,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'News.Deleted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'newsId' => $this->newsId,
            'slug' => $this->slug,
            'title' => $this->title,
        ];
    }

    public function summary(): string
    {
        return trans('news::events.deleted.summary', [
            'id' => $this->newsId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            newsId: (int) ($payload['newsId'] ?? 0),
            slug: (string) ($payload['slug'] ?? ''),
            title: (string) ($payload['title'] ?? ''),
        );
    }
}
