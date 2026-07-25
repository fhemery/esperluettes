<?php

namespace App\Domains\Discord\Tests\Fixtures;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * Notification content whose display() exercises every construct handled by the
 * HTML -> Discord markdown conversion (link, bold, italic, line break, entity).
 *
 * The stored payload deliberately uses different keys from the rendered output,
 * so tests cannot confuse "payload returned verbatim" with "payload derived
 * from the rendered text".
 */
class HtmlTestNotificationContent implements NotificationContent
{
    public function __construct(
        public readonly string $linkUrl = 'https://example.com',
        public readonly string $linkLabel = 'click here',
        public readonly string $emphasis = 'important',
    ) {}

    public static function type(): string
    {
        return 'discord.test.html_notification';
    }

    public function toData(): array
    {
        return [
            'link_url' => $this->linkUrl,
            'link_label' => $this->linkLabel,
            'emphasis' => $this->emphasis,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            linkUrl: (string) ($data['link_url'] ?? 'https://example.com'),
            linkLabel: (string) ($data['link_label'] ?? 'click here'),
            emphasis: (string) ($data['emphasis'] ?? 'important'),
        );
    }

    public function display(): string
    {
        return '<a href="' . $this->linkUrl . '">' . $this->linkLabel . '</a>'
            . ' <strong>' . $this->emphasis . '</strong>'
            . ' <em>note</em><br>Caf&eacute;';
    }
}
