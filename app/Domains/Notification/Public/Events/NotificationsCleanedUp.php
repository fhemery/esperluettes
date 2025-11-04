<?php

namespace App\Domains\Notification\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class NotificationsCleanedUp implements DomainEvent
{
    public function __construct(
        public readonly int $oldNotificationsDeleted,
        public readonly int $unknownTypesDeleted,
    ) {}

    public static function name(): string
    {
        return 'Notification.CleanedUp';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'old_notifications_deleted' => $this->oldNotificationsDeleted,
            'unknown_types_deleted' => $this->unknownTypesDeleted,
            'total' => $this->total(),
        ];
    }

    public function summary(): string
    {
        return __('notifications::events.notifications_cleaned_up', [
            'total' => $this->total(),
            'old_deleted' => $this->oldNotificationsDeleted,
            'unknown_deleted' => $this->unknownTypesDeleted,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            oldNotificationsDeleted: (int) ($payload['old_notifications_deleted'] ?? 0),
            unknownTypesDeleted: (int) ($payload['unknown_types_deleted'] ?? 0),
        );
    }

    public function total(): int
    {
        return $this->oldNotificationsDeleted + $this->unknownTypesDeleted;
    }
}
