<?php

namespace App\Domains\Moderation\Public\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

final class ReportSubmittedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $userName,
    ) {}

    public static function type(): string
    {
        return 'moderation.report.submitted';
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
        return __('moderation::notifications.report_submitted', [
            'user_name' => e($this->userName),
            'url' => route('moderation.admin.moderation-reports.index'),
        ]);
    }
}
