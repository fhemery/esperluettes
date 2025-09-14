<?php

namespace App\Domains\StaticPage\Events;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;

class StaticPageUpdated implements DomainEvent, AuditableEvent
{
    /**
     * @param array<int, string> $changedFields
     */
    public function __construct(
        public readonly int $pageId,
        public readonly string $slug,
        public readonly string $title,
        public readonly array $changedFields,
    ) {}

    public static function name(): string { return 'StaticPage.Updated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'pageId' => $this->pageId,
            'slug' => $this->slug,
            'title' => $this->title,
            'changedFields' => $this->changedFields,
        ];
    }

    public function summary(): string
    {
        return trans('static::events.updated.summary', [
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
            changedFields: array_values((array) ($payload['changedFields'] ?? [])),
        );
    }
}
