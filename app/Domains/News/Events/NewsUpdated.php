<?php

namespace App\Domains\News\Events;

use App\Domains\Events\Contracts\DomainEvent;

class NewsUpdated implements DomainEvent
{
    /**
     * @param array<int, string> $changedFields
     */
    public function __construct(
        public readonly int $newsId,
        public readonly string $slug,
        public readonly string $title,
        public readonly array $changedFields,
    ) {}

    public static function name(): string { return 'News.Updated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'newsId' => $this->newsId,
            'slug' => $this->slug,
            'title' => $this->title,
            'changedFields' => $this->changedFields,
        ];
    }

    public function summary(): string
    {
        return trans('news::events.updated.summary', [
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
            changedFields: array_values((array) ($payload['changedFields'] ?? [])),
        );
    }
}
