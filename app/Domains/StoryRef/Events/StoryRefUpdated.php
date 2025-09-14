<?php

namespace App\Domains\StoryRef\Events;

use App\Domains\Events\Contracts\DomainEvent;

class StoryRefUpdated implements DomainEvent
{
    /**
     * @param array<int, string> $changedFields
     */
    public function __construct(
        public readonly string $refKind,
        public readonly int $refId,
        public readonly string $refSlug,
        public readonly string $refName,
        public readonly array $changedFields,
    ) {}

    public static function name(): string { return 'StoryRef.Updated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'refKind' => $this->refKind,
            'refId' => $this->refId,
            'refSlug' => $this->refSlug,
            'refName' => $this->refName,
            'changedFields' => $this->changedFields,
        ];
    }

    public function summary(): string
    {
        return trans('story_ref::events.updated.summary', [
            'kind' => $this->refKind,
            'slug' => $this->refSlug,
            'id' => $this->refId,
            'name' => $this->refName,
            'fields' => implode(',', $this->changedFields),
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            refKind: (string) ($payload['refKind'] ?? ''),
            refId: (int) ($payload['refId'] ?? 0),
            refSlug: (string) ($payload['refSlug'] ?? ''),
            refName: (string) ($payload['refName'] ?? ''),
            changedFields: array_values((array) ($payload['changedFields'] ?? [])),
        );
    }
}
