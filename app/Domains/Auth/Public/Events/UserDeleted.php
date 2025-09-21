<?php

namespace App\Domains\Auth\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class UserDeleted implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public static function name(): string { return 'Auth.UserDeleted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
        );
    }

    public function summary(): string
    {
        return trans('auth::events.user_deleted.summary', [
            'id' => $this->userId,
        ]);
    }
}
