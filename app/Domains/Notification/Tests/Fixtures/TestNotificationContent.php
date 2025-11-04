<?php

namespace App\Domains\Notification\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class TestNotificationContent implements NotificationContent
{
    public function __construct(
        public readonly string $message = 'Test notification',
    ) {}

    public static function type(): string
    {
        return 'test.notification';
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
            message: (string) ($data['message'] ?? 'Test notification'),
        );
    }

    public function display(): string
    {
        return $this->message;
    }
}
