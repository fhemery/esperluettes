<?php

namespace App\Domains\Story\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class ChapterCommentNotificationsBackfilled implements DomainEvent
{
    public function __construct(
        public readonly int $eventsProcessed,
        public readonly int $notificationsCreated,
        public readonly int $notificationsDeleted,
        public readonly int $skipped,
    ) {}

    public static function name(): string
    {
        return 'Story.ChapterCommentNotificationsBackfilled';
    }

    public static function version(): int
    {
        return 1;
    }

    public function toPayload(): array
    {
        return [
            'events_processed' => $this->eventsProcessed,
            'notifications_created' => $this->notificationsCreated,
            'notifications_deleted' => $this->notificationsDeleted,
            'skipped' => $this->skipped,
        ];
    }

    public function summary(): string
    {
        return trans('story::events.chapter_comment_notifications_backfilled.summary', [
            'processed' => $this->eventsProcessed,
            'created' => $this->notificationsCreated,
            'deleted' => $this->notificationsDeleted,
            'skipped' => $this->skipped,
        ]);
    }

    public static function fromPayload(array $payload): static
    {
        return new static(
            eventsProcessed: (int) ($payload['events_processed'] ?? 0),
            notificationsCreated: (int) ($payload['notifications_created'] ?? 0),
            notificationsDeleted: (int) ($payload['notifications_deleted'] ?? 0),
            skipped: (int) ($payload['skipped'] ?? 0),
        );
    }
}
