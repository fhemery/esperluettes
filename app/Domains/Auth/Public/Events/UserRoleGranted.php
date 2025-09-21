<?php

namespace App\Domains\Auth\Public\Events;

use App\Domains\Events\Public\Contracts\AuditableEvent;
use App\Domains\Events\Public\Contracts\DomainEvent;

class UserRoleGranted implements DomainEvent, AuditableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $role,
        public readonly ?int $actorUserId,
        public readonly bool $targetIsAdmin,
    ) {}

    public static function name(): string { return 'Auth.UserRoleGranted'; }

    public static function version(): int { return 1; }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'role' => $this->role,
            'actorUserId' => $this->actorUserId,
            'targetIsAdmin' => $this->targetIsAdmin,
        ];
    }

    public function summary(): string
    {
        $isSystem = $this->actorUserId !== null && $this->actorUserId === $this->userId && !$this->targetIsAdmin;
        $key = $isSystem ? 'auth::events.user_role_granted.system.summary' : 'auth::events.user_role_granted.summary';
        return trans($key, [
            'role' => $this->role,
            'id' => $this->userId,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            userId: (int) ($payload['userId'] ?? 0),
            role: (string) ($payload['role'] ?? ''),
            actorUserId: isset($payload['actorUserId']) ? (int) $payload['actorUserId'] : null,
            targetIsAdmin: (bool) ($payload['targetIsAdmin'] ?? false),
        );
    }
}
