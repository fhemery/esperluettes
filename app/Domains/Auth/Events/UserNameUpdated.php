<?php

namespace App\Domains\Auth\Events;

use App\Domains\Shared\Contracts\SummarizableDomainEvent;

class UserNameUpdated implements SummarizableDomainEvent
{
    public function __construct(
        public int $userId,
        public string $oldName,
        public string $newName,
        public ?\DateTimeInterface $changedAt = null,
    ) {}

    public static function summarizePayload(array $payload): string
    {
        $old = $payload['oldName'] ?? null;
        $new = $payload['newName'] ?? null;
        $id = $payload['userId'] ?? 'n/a';
        if ($old && $new) {
            return "User #{$id} changed name from {$old} to {$new}";
        }
        return "User #{$id} updated name";
    }
}
