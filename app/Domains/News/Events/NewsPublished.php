<?php

namespace App\Domains\News\Events;

use App\Domains\Events\Contracts\DomainEvent;

class NewsPublished implements DomainEvent
{
    public function __construct(
        public readonly int $newsId,
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $publishedAt,
    ) {}

    public static function name(): string { return 'News.Published'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'newsId' => $this->newsId,
            'slug' => $this->slug,
            'title' => $this->title,
            'publishedAt' => $this->publishedAt,
        ];
    }

    public function summary(): string
    {
        return trans('news::events.published.summary', [
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
            publishedAt: isset($payload['publishedAt']) ? (string) $payload['publishedAt'] : null,
        );
    }
}
