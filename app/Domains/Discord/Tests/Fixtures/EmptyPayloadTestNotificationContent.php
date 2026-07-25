<?php

namespace App\Domains\Discord\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * Notification content with no stored data at all, used to verify that the
 * pending API still serialises `data` as a JSON object ({}) rather than an
 * empty JSON array ([]).
 */
class EmptyPayloadTestNotificationContent implements NotificationContent
{
    public static function type(): string
    {
        return 'discord.test.empty_payload_notification';
    }

    public function toData(): array
    {
        return [];
    }

    public static function fromData(array $data): static
    {
        return new static();
    }

    public function display(): string
    {
        return 'Rien à signaler';
    }
}
