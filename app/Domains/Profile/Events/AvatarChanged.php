<?php

namespace App\Domains\Profile\Events;

use App\Domains\Events\Contracts\DomainEvent;

class AvatarChanged implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $profilePicturePath,
    ) {}

    public static function name(): string { return 'Profile.AvatarChanged'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'profilePicturePath' => $this->profilePicturePath,
        ];
    }

    public function summary(): string
    {
        if ($this->profilePicturePath === null) {
            return trans('profile::events.avatar_changed.summary_deleted');
        }
        return trans('profile::events.avatar_changed.summary');
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            profilePicturePath: $payload['profilePicturePath'] ?? null,
        );
    }
}
