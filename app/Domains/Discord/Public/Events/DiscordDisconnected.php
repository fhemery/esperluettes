<?php

namespace App\Domains\Discord\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

final class DiscordDisconnected implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $discordId,
    ) {}

    public static function name(): string
    {
        return 'Discord.Disconnected';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'discordId' => $this->discordId,
        ];
    }

    public function summary(): string
    {
        return trans('discord::events.disconnected.summary', [
            'userId' => $this->userId,
            'discordId' => $this->discordId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            (int) ($payload['userId'] ?? 0),
            (string) ($payload['discordId'] ?? ''),
        );
    }
}
