<?php

namespace App\Domains\Discord\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * Notification content whose display() returns HTML with a link, for testing
 * the HTML → Discord markdown conversion in the pending API response.
 */
class HtmlTestNotificationContent implements NotificationContent
{
    public static function type(): string
    {
        return 'discord.test.html_notification';
    }

    public function toData(): array
    {
        return ['message' => 'click here'];
    }

    public static function fromData(array $data): static
    {
        return new static();
    }

    public function display(): string
    {
        return '<a href="https://example.com">click here</a>';
    }
}
