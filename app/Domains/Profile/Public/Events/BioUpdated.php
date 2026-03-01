<?php

namespace App\Domains\Profile\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class BioUpdated implements DomainEvent
{
    public function __construct(
        public int $userId,
        public ?string $description,
        public ?string $facebookHandle,
        public ?string $xHandle,
        public ?string $instagramHandle,
        public ?string $youtubeHandle,
        public ?string $tiktokHandle,
        public ?string $blueskyHandle,
        public ?string $mastodonHandle,
    ) {}

    public static function name(): string { return 'Profile.BioUpdated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'description' => $this->description,
            'facebookHandle' => $this->facebookHandle,
            'xHandle' => $this->xHandle,
            'instagramHandle' => $this->instagramHandle,
            'youtubeHandle' => $this->youtubeHandle,
            'tiktokHandle' => $this->tiktokHandle,
            'blueskyHandle' => $this->blueskyHandle,
            'mastodonHandle' => $this->mastodonHandle,
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
            facebookHandle: $payload['facebookHandle'] ?? null,
            xHandle: $payload['xHandle'] ?? null,
            instagramHandle: $payload['instagramHandle'] ?? null,
            youtubeHandle: $payload['youtubeHandle'] ?? null,
            tiktokHandle: $payload['tiktokHandle'] ?? null,
            blueskyHandle: $payload['blueskyHandle'] ?? null,
            mastodonHandle: $payload['mastodonHandle'] ?? null,
        );
    }
}
