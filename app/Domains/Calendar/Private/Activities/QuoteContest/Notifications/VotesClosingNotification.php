<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * "24 h left to vote in contest X" — broadcast to confirmed users a day before
 * the activity ends (spec §4.7, decisions #9 and #10).
 *
 * Deep-links to the ballot through the `#votes` hash, like
 * {@see VotesOpenNotification}: a reminder to vote that lands anywhere else
 * costs the reader a click.
 */
class VotesClosingNotification implements NotificationContent
{
    public function __construct(
        public readonly string $activityName,
        public readonly string $activitySlug,
    ) {
    }

    public static function type(): string
    {
        return 'calendar.quote_contest.votes_closing';
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

        return __('quote-contest::quote-contest.notification.votes_closing.display', [
            'activity_name' => $this->activityName,
            'activity_url' => $activityUrl,
        ]);
    }
}
