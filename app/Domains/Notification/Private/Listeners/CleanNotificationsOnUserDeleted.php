<?php

namespace App\Domains\Notification\Private\Listeners;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Notification\Private\Services\NotificationService;

class CleanNotificationsOnUserDeleted
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function handle(UserDeleted $event): void
    {
        // Remove all notification_reads rows for the deleted user
        $this->notifications->deleteAllNotificationReadsForUser($event->userId);
        
        // Remove all notifications where the deleted user was the source
        $this->notifications->deleteNotificationsBySourceUser($event->userId);
    }
}
