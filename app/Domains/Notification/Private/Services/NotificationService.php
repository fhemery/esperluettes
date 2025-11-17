<?php

namespace App\Domains\Notification\Private\Services;

use App\Domains\Notification\Private\Models\Notification;
use App\Domains\Notification\Public\Contracts\NotificationContent;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Persist a notification and create unread rows for target users.
     *
     * @param int[] $userIds
     * @param \DateTime|null $createdAt Optional timestamp for backfilling
     */
    public function createNotification(array $userIds, NotificationContent $content, ?int $sourceUserId = null, ?\DateTime $createdAt = null): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return;
        }

        $timestamp = $createdAt ?? now();
        
        // Normal creation with automatic timestamps
        $notification = Notification::query()->create([
            'source_user_id' => $sourceUserId,
            'content_key' => $content::type(),
            'content_data' => $content->toData(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);

        $rows = [];
        foreach ($userIds as $uid) {
            $rows[] = [
                'notification_id' => $notification->id,
                'user_id' => (int) $uid,
                'read_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
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
     * List notifications for a user with pagination and optional read filter.
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param bool $showRead If false, only returns unread notifications
     * @return array<int,array<string,mixed>>
     */
    public function listForUser(int $userId, int $limit = 20, int $offset = 0, bool $showRead = false): array
    {
        $query = DB::table('notification_reads as nr')
            ->join('notifications as n', 'n.id', '=', 'nr.notification_id')
            ->where('nr.user_id', (int) $userId)
            ->orderByDesc('n.created_at');

        // Filter out read notifications if showRead is false
        if (!$showRead) {
            $query->whereNull('nr.read_at');
        }

        $rows = $query->limit($limit)
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

    /**
     * Delete notifications older than the specified cutoff date.
     * Returns the number of deleted notifications.
     */
    public function deleteOlderThan(\DateTimeInterface $cutoffDate): int
    {
        return DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Delete notifications with types that are not in the provided list.
     * If the list is empty, deletes all notifications.
     * Returns the number of deleted notifications.
     *
     * @param array<int, string> $registeredTypes
     */
    public function deleteUnknownTypes(array $registeredTypes): int
    {
        if (empty($registeredTypes)) {
            return DB::table('notifications')->delete();
        }

        return DB::table('notifications')
            ->whereNotIn('content_key', $registeredTypes)
            ->delete();
    }

    /**
     * Delete all notifications of a specific type.
     * Returns the number of deleted notifications.
     * Cascade deletion will automatically remove associated notification_reads rows.
     */
    public function deleteNotificationsByType(string $contentKey): int
    {
        return DB::table('notifications')
            ->where('content_key', $contentKey)
            ->delete();
    }

    /**
     * Count notifications of a specific type.
     */
    public function countNotificationsByType(string $contentKey): int
    {
        return DB::table('notifications')
            ->where('content_key', $contentKey)
            ->count();
    }

    /**
     * Delete all notification_reads rows for the given user.
     * Returns the number of deleted rows.
     */
    public function deleteAllNotificationReadsForUser(int $userId): int
    {
        return DB::table('notification_reads')
            ->where('user_id', (int) $userId)
            ->delete();
    }

    /**
     * Delete all notifications where the user was the source.
     * Returns the number of deleted notifications.
     */
    public function deleteNotificationsBySourceUser(int $sourceUserId): int
    {
        return DB::table('notifications')
            ->where('source_user_id', (int) $sourceUserId)
            ->delete();
    }
}
