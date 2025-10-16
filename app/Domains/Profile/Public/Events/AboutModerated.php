<?php

namespace App\Domains\Profile\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class AboutModerated implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public static function name(): string { return 'Profile.AboutModerated'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
        ];
    }

    public function summary(): string
    {
        return trans('profile::events.about_moderated.summary', ['userId' => $this->userId]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
        );
    }
}
