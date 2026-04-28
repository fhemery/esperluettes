<?php

namespace App\Domains\Notification\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

class ForcedTestNotificationContent implements NotificationContent
{
    public static function type(): string
    {
        return 'test.forced.notification';
    }

    public function toData(): array
    {
        return ['forced' => true];
    }

    public static function fromData(array $data): static
    {
        return new static();
    }

    public function display(): string
    {
        return 'Forced test notification';
    }
}
