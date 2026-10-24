<?php

namespace App\Domains\Quote\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class UserQuotesRemoved implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $count,
    ) {
    }

    public static function name(): string
    {
        return 'Quote.UserQuotesRemoved';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'count' => $this->count,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            count: (int) ($payload['count'] ?? 0),
        );
    }

    public function summary(): string
    {
        return "{$this->count} quote(s) of user #{$this->userId} removed";
    }
}
