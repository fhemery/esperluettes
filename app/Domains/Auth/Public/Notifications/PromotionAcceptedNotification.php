<?php

namespace App\Domains\Auth\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

final class PromotionAcceptedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $userName,
    ) {}

    public static function type(): string
    {
        return 'auth.promotion.accepted';
    }

    public function toData(): array
    {
        return [
            'user_name' => $this->userName,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            userName: (string) ($data['user_name'] ?? ''),
        );
    }

    public function display(): string
    {
        return __('auth::notification.promotion_accepted');
    }
}
