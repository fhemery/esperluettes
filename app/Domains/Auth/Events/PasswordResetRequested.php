<?php

namespace App\Domains\Auth\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class PasswordResetRequested implements DomainEvent, AuditableEvent
{
    public function __construct(
        public string $email,
        public ?int $userId,
    ) {}

    public static function name(): string { return 'Auth.PasswordResetRequested'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'email' => $this->email,
            'userId' => $this->userId,
        ];
    }

    public function summary(): string
    {
        return trans('auth::events.password_reset_requested.summary', [
            'email' => $this->email,
            'id' => $this->userId ?? '—',
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            email: (string) ($payload['email'] ?? ''),
            userId: isset($payload['userId']) ? (int) $payload['userId'] : null,
        );
    }
}
