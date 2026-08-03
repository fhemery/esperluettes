<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * "The votes of contest X are open" — broadcast to confirmed users when the
 * vote period starts (spec §4.7, decisions #9 and #10).
 *
 * The link carries the `#votes` hash: the activity page's tabs are tracked in
 * the URL hash, so the reader lands on the ballot rather than on the first tab.
 */
class VotesOpenNotification implements NotificationContent
{
    public function __construct(
        public readonly string $activityName,
        public readonly string $activitySlug,
    ) {
    }

    public static function type(): string
    {
        return 'calendar.quote_contest.votes_open';
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
            ? route('calendar.activities.show', $this->activitySlug) . '#votes'
            : '';

        return __('quote-contest::quote-contest.notification.votes_open.display', [
            'activity_name' => $this->activityName,
            'activity_url' => $activityUrl,
        ]);
    }
}
