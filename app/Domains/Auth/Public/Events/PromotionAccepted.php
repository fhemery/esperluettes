<?php

namespace App\Domains\Auth\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class PromotionAccepted implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $decidedBy,
    ) {}

    public static function name(): string { return 'Auth.PromotionAccepted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'decidedBy' => $this->decidedBy,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            decidedBy: (int) ($payload['decidedBy'] ?? 0),
        );
    }

    public function summary(): string
    {
        return trans('auth::events.promotion_accepted.summary', [
            'id' => $this->userId,
        ]);
    }
}
