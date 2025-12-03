<?php

namespace App\Domains\Auth\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class PromotionRejected implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $decidedBy,
        public readonly string $reason,
    ) {}

    public static function name(): string { return 'Auth.PromotionRejected'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'decidedBy' => $this->decidedBy,
            'reason' => $this->reason,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            decidedBy: (int) ($payload['decidedBy'] ?? 0),
            reason: (string) ($payload['reason'] ?? ''),
        );
    }

    public function summary(): string
    {
        return trans('auth::events.promotion_rejected.summary', [
            'id' => $this->userId,
        ]);
    }
}
