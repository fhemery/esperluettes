<?php

namespace App\Domains\Auth\Events;

use App\Domains\Shared\Contracts\SummarizableDomainEvent;

class UserRegistered implements SummarizableDomainEvent
{
    public function __construct(
        public int $userId,
        public ?string $name,
        public ?\DateTimeInterface $registeredAt = null,
    ) {}

    public static function summarizePayload(array $payload): string
    {
        $id = $payload['userId'] ?? 'n/a';
        $name = $payload['name'] ?? null;
        if ($name) {
            return "User #{$id} registered as {$name}";
        }
        return "User #{$id} registered";
    }
}
