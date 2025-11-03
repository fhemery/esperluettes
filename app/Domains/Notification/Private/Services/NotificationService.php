<?php

namespace App\Domains\Notification\Private\Services;

use App\Domains\Notification\Private\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Persist a notification and create unread rows for target users.
     *
     * @param int[] $userIds
     */
    public function createNotification(array $userIds, string $contentKey, array $contentData, ?int $sourceUserId = null): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return;
        }

        $notification = Notification::query()->create([
            'source_user_id' => $sourceUserId,
            'content_key' => $contentKey,
            'content_data' => $contentData,
        ]);

        $now = now();
        $rows = [];
        foreach ($userIds as $uid) {
            $rows[] = [
                'notification_id' => $notification->id,
                'user_id' => (int) $uid,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('notification_reads')->insert($rows);
    }

    public function getUnreadCount(int $userId): int
    {
        return (int) DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->whereNull('read_at')
            ->count();
    }
}
