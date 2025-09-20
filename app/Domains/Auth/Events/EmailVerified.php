<?php

namespace App\Domains\Auth\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Events\Public\Contracts\AuditableEvent;

class EmailVerified implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
    ) {}

    public static function name(): string { return 'Auth.EmailVerified'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
        ];
    }

    public function summary(): string
    {
        return trans('auth::events.email_verified.summary');
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
        );
    }
}
