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

    /**
     * List notifications for a user ordered by notifications.created_at DESC.
     * Returns simple arrays for blade consumption.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listForUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $rows = DB::table('notification_reads as nr')
            ->join('notifications as n', 'n.id', '=', 'nr.notification_id')
            ->where('nr.user_id', (int) $userId)
            ->orderByDesc('n.created_at')
            ->limit($limit)
            ->offset($offset)
            ->get([
                'n.id as id',
                'n.content_key as content_key',
                'n.content_data as content_data',
                'n.source_user_id as source_user_id',
                'n.created_at as created_at',
                'nr.read_at as read_at',
            ]);

        return $rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'content_key' => $r->content_key,
                'content_data' => is_string($r->content_data) ? json_decode($r->content_data, true) : $r->content_data,
                'source_user_id' => $r->source_user_id !== null ? (int) $r->source_user_id : null,
                'created_at' => $r->created_at,
                'read_at' => $r->read_at,
            ];
        })->all();
    }

    /**
     * Mark a notification as read for the given user.
     * - If the notification does not belong to the user, do nothing.
     * - If already read, do nothing (idempotent).
     */
    public function markAsRead(int $userId, int $notificationId): void
    {
        $row = DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->where('notification_id', (int) $notificationId)
            ->first(['read_at']);

        if (!$row) {
            // Not owned by user → do nothing
            return;
        }

        if ($row->read_at !== null) {
            // Already read → idempotent
            return;
        }

        DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->where('notification_id', (int) $notificationId)
            ->update(['read_at' => now()]);
    }

    /**
     * Mark a notification as UNREAD for the given user.
     * - If the notification does not belong to the user, do nothing.
     * - If already unread, do nothing (idempotent).
     */
    public function markAsUnread(int $userId, int $notificationId): void
    {
        $row = DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->where('notification_id', (int) $notificationId)
            ->first(['read_at']);

        if (!$row) {
            return;
        }

        if ($row->read_at === null) {
            return;
        }

        DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->where('notification_id', (int) $notificationId)
            ->update(['read_at' => null]);
    }

    /**
     * Mark all notifications as read for the given user (idempotent).
     */
    public function markAllAsRead(int $userId): void
    {
        DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
