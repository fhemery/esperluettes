<?php

namespace App\Domains\StoryRef\Events;

use App\Domains\Events\Contracts\DomainEvent;

class StoryRefAdded implements DomainEvent
{
    public function __construct(
        public readonly string $refKind,
        public readonly int $refId,
        public readonly string $refSlug,
        public readonly string $refName,
    ) {}

    public static function name(): string { return 'StoryRef.Added'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'refKind' => $this->refKind,
            'refId' => $this->refId,
            'refSlug' => $this->refSlug,
            'refName' => $this->refName,
        ];
    }

    public function summary(): string
    {
        return trans('story_ref::events.added.summary', [
            'kind' => $this->refKind,
            'slug' => $this->refSlug,
            'id' => $this->refId,
            'name' => $this->refName,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            refKind: (string) ($payload['refKind'] ?? ''),
            refId: (int) ($payload['refId'] ?? 0),
            refSlug: (string) ($payload['refSlug'] ?? ''),
            refName: (string) ($payload['refName'] ?? ''),
        );
    }
}
