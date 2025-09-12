<?php

namespace App\Domains\Auth\Events;

use App\Domains\Events\Contracts\DomainEvent;
use App\Domains\Events\Contracts\AuditableEvent;

class UserRegistered implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $userId,
        public ?string $displayName,
    ) {}

    public static function name(): string { return 'Auth.UserRegistered'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'displayName' => $this->displayName,
        ];
    }

    public function summary(): string
    {
        return trans('auth::events.user_registered.summary', [
            'name' => $this->displayName ?? '—',
            'id' => $this->userId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            displayName: $payload['displayName'] ?? null,
        );
    }
}
