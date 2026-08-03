<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * "24 h left to submit to contest X" — broadcast to confirmed users a day
 * before the submissions close (spec §4.7, decisions #9 and #10).
 *
 * Like every notification here it carries the activity's name and slug only,
 * and looks nothing up when displayed.
 */
class SubmissionsClosingNotification implements NotificationContent
{
    public function __construct(
        public readonly string $activityName,
        public readonly string $activitySlug,
    ) {
    }

    public static function type(): string
    {
        return 'calendar.quote_contest.submissions_closing';
    }

    public function toData(): array
    {
        return [
            'activity_name' => $this->activityName,
            'activity_slug' => $this->activitySlug,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            activityName: (string) ($data['activity_name'] ?? ''),
            activitySlug: (string) ($data['activity_slug'] ?? ''),
        );
    }

    public function display(): string
    {
        $activityUrl = $this->activitySlug !== ''
            ? route('calendar.activities.show', $this->activitySlug)
            : '';

        return __('quote-contest::quote-contest.notification.submissions_closing.display', [
            'activity_name' => $this->activityName,
            'activity_url' => $activityUrl,
        ]);
    }
}
