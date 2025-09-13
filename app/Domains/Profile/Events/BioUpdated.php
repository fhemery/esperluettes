<?php

namespace App\Domains\Profile\Events;

use App\Domains\Events\Contracts\AuditableEvent;
use App\Domains\Events\Contracts\DomainEvent;

class BioUpdated implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $userId,
        public ?string $description,
        public ?string $facebookUrl,
        public ?string $xUrl,
        public ?string $instagramUrl,
        public ?string $youtubeUrl,
    ) {}

    public static function name(): string { return 'Profile.BioUpdated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'description' => $this->description,
            'facebookUrl' => $this->facebookUrl,
            'xUrl' => $this->xUrl,
            'instagramUrl' => $this->instagramUrl,
            'youtubeUrl' => $this->youtubeUrl,
        ];
    }

    public function summary(): string
    {
        return trans('profile::events.bio_updated.summary');
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            description: $payload['description'] ?? null,
            facebookUrl: $payload['facebookUrl'] ?? null,
            xUrl: $payload['xUrl'] ?? null,
            instagramUrl: $payload['instagramUrl'] ?? null,
            youtubeUrl: $payload['youtubeUrl'] ?? null,
        );
    }
}
