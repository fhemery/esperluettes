<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Notifications;

use App\Domains\Notification\Public\Contracts\NotificationContent;

/**
 * "Your entry in category X was removed by moderation, and the slot is free
 * again" (decision #11, spec §4.6.4).
 *
 * Everything it displays is captured here, at creation time: the entry it talks
 * about is gone by the time this is read, so there would be nothing to look up
 * even if reading the database were allowed. It generates the contest's URL,
 * which is all it does dynamically.
 *
 * It deliberately carries **neither the passage nor the reader's note**
 * (assumption A1): the category and the contest are enough to act on, and a
 * notification is a wider surface than the contest page it came from.
 */
class EntryRemovedNotification implements NotificationContent
{
    public function __construct(
        public readonly string $categoryTitle,
        public readonly string $activitySlug,
        public readonly string $activityName,
    ) {
    }

    public static function type(): string
    {
        return 'calendar.quote_contest.entry_removed';
    }

    public function toData(): array
    {
        return [
            'category_title' => $this->categoryTitle,
            'activity_slug' => $this->activitySlug,
            'activity_name' => $this->activityName,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            categoryTitle: (string) ($data['category_title'] ?? ''),
            activitySlug: (string) ($data['activity_slug'] ?? ''),
            activityName: (string) ($data['activity_name'] ?? ''),
        );
    }

    public function display(): string
    {
        $activityUrl = $this->activitySlug !== ''
            ? route('calendar.activities.show', $this->activitySlug)
            : '';

        return __('quote-contest::quote-contest.notification.entry_removed.display', [
            'category' => $this->categoryTitle,
            'activity_name' => $this->activityName,
            'activity_url' => $activityUrl,
        ]);
    }
}
