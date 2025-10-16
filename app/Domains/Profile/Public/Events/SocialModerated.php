<?php

namespace App\Domains\Profile\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class SocialModerated implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public static function name(): string { return 'Profile.SocialModerated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
        ];
    }

    public function summary(): string
    {
        return trans('profile::events.social_moderated.summary', ['userId' => $this->userId]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
        );
    }
}
