<?php

namespace App\Domains\Follow\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class UserFollowed implements DomainEvent
{
    public function __construct(
        public readonly int $followerId,
        public readonly int $followedId,
    ) {}

    public static function name(): string { return 'Follow.UserFollowed'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'follower_id' => $this->followerId,
            'followed_id' => $this->followedId,
        ];
    }

    public function summary(): string
    {
        return "User {$this->followerId} followed user {$this->followedId}";
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            followerId: (int) ($payload['follower_id'] ?? 0),
            followedId: (int) ($payload['followed_id'] ?? 0),
        );
    }
}
