<?php

namespace App\Domains\Auth\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class UserLoggedIn implements DomainEvent, AuditableEvent
{
    public function __construct(
        public int $userId,
    ) {}

    public static function name(): string { return 'Auth.UserLoggedIn'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
        ];
    }

    public function summary(): string
    {
        return trans('auth::events.user_logged_in.summary');
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
        );
    }
}
