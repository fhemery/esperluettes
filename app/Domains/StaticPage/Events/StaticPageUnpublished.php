<?php

namespace App\Domains\StaticPage\Events;

use App\Domains\Events\Contracts\DomainEvent;

class StaticPageUnpublished implements DomainEvent
{
    public function __construct(
        public readonly int $pageId,
        public readonly string $slug,
        public readonly string $title,
    ) {}

    public static function name(): string { return 'StaticPage.Unpublished'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'pageId' => $this->pageId,
            'slug' => $this->slug,
            'title' => $this->title,
        ];
    }

    public function summary(): string
    {
        return trans('static::events.unpublished.summary', [
            'id' => $this->pageId,
            'title' => $this->title,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            pageId: (int) ($payload['pageId'] ?? 0),
            slug: (string) ($payload['slug'] ?? ''),
            title: (string) ($payload['title'] ?? ''),
        );
    }
}
