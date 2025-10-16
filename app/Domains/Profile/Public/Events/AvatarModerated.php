<?php

namespace App\Domains\Profile\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class AvatarModerated implements DomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $profilePicturePath,
    ) {}

    public static function name(): string { return 'Profile.AvatarModerated'; }

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
        return trans('profile::events.avatar_moderated.summary', ['userId' => $this->userId]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            profilePicturePath: $payload['profilePicturePath'] ?? null,
        );
    }
}
