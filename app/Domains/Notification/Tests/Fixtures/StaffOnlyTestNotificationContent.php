<?php

namespace App\Domains\Notification\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class StaffOnlyTestNotificationContent implements NotificationContent
{
    public function __construct(
        public readonly string $message = 'Staff-only test notification',
    ) {}

    public static function type(): string
    {
        return 'test.staff.notification';
    }

    public function toData(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            message: (string) ($data['message'] ?? 'Staff-only test notification'),
        );
    }

    public function display(): string
    {
        return $this->message;
    }
}
