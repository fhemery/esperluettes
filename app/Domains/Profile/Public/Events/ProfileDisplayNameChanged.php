<?php

namespace App\Domains\Profile\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class ProfileDisplayNameChanged implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $oldDisplayName,
        public readonly string $newDisplayName,
    ) {}

    public static function name(): string { return 'Profile.DisplayNameChanged'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'oldDisplayName' => $this->oldDisplayName,
            'newDisplayName' => $this->newDisplayName,
        ];
    }

    public function summary(): string
    {
        return trans('profile::events.display_name_changed.summary', [
            'id' => $this->userId,
            'old' => $this->oldDisplayName,
            'new' => $this->newDisplayName,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            oldDisplayName: (string) ($payload['oldDisplayName'] ?? ''),
            newDisplayName: (string) ($payload['newDisplayName'] ?? ''),
        );
    }
}

