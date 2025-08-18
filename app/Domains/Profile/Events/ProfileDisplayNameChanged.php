<?php

namespace App\Domains\Profile\Events;

use App\Domains\Shared\Contracts\SummarizableDomainEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfileDisplayNameChanged implements SummarizableDomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $oldDisplayName,
        public readonly string $newDisplayName,
        public readonly \DateTimeInterface $changedAt,
    ) {
    }

    public static function summarizePayload(array $payload): string
    {
        $old = $payload['oldDisplayName'] ?? null;
        $new = $payload['newDisplayName'] ?? null;
        $id = $payload['userId'] ?? 'n/a';
        if ($old && $new) {
            return "User #{$id} changed display name from {$old} to {$new}";
        }
        return "User #{$id} updated display name";
    }
}
