<?php

use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Contracts\NotificationContent;
use App\Domains\Notification\Tests\Fixtures\TestNotificationContent;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Create a notification for multiple users via Public API, and optionally override created_at.
 * Returns the created notification ID.
 * @param array<int,int> $userIds
 */
function makeNotification(array $userIds, ?NotificationContent $content = null, ?int $sourceUserId = null, ?string $createdAt = null): int
{
    /** @var NotificationPublicApi $api */
    $api = app(NotificationPublicApi::class);
    
    $content = $content ?? new TestNotificationContent();
    
    // Register test notification type (factory will store if not already there)
    $factory = app(\App\Domains\Notification\Public\Services\NotificationFactory::class);
    try {
        $factory->register(TestNotificationContent::type(), TestNotificationContent::class);
    } catch (\InvalidArgumentException $e) {
        // Already registered, ignore
    }
    
    $api->createNotification($userIds, $content, $sourceUserId ?? ($userIds[0] ?? null));

    // Fetch last inserted notification id
    $row = \Illuminate\Support\Facades\DB::table('notifications')->orderByDesc('id')->first(['id']);
    $notificationId = (int) ($row->id ?? 0);

    if ($createdAt !== null && $notificationId > 0) {
        \Illuminate\Support\Facades\DB::table('notifications')->where('id', $notificationId)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    return $notificationId;
}

/**
 * Fetch the notification_reads row for a given user/notification pair.
 * Returns stdClass|null with at least read_at field.
 */
function notificationReadRow(int $userId, int $notificationId): ?object
{
    return \Illuminate\Support\Facades\DB::table('notification_reads')
        ->where('notification_id', $notificationId)
        ->where('user_id', $userId)
        ->first();
}

/**
 * Send the mark-as-read request for a given notification ID, asserting 204.
 */
function markNotificationAsRead(TestCase $t, int $notificationId): void
{
    $t->postJson(route('notifications.markRead', $notificationId))
        ->assertNoContent();
}

/**
 * Send the mark-as-unread request for a given notification ID, asserting 204.
 */
function markNotificationAsUnread(TestCase $t, int $notificationId): void
{
    $t->postJson(route('notifications.markUnread', $notificationId))
        ->assertNoContent();
}

/**
 * Send the mark-all-as-read request for the current user, asserting 204.
 */
function markAllNotificationsAsRead(TestCase $t): void
{
    $t->postJson(route('notifications.markAllRead'))
        ->assertNoContent();
}

/**
 * Find the most recent notification by its content key. Returns stdClass or null.
 * The returned object includes: id, content_key, content_data (JSON string or array depending on driver), source_user_id, created_at, updated_at.
 */
function getLatestNotificationByKey(string $contentKey): ?object
{
    return \Illuminate\Support\Facades\DB::table('notifications')
        ->where('content_key', $contentKey)
        ->orderByDesc('id')
        ->first();
}

/**
 * Return target user IDs for a given notification ID (from notification_reads table).
 * @return array<int,int>
 */
function getNotificationTargetUserIds(int $notificationId): array
{
    return \Illuminate\Support\Facades\DB::table('notification_reads')
        ->where('notification_id', $notificationId)
        ->pluck('user_id')
        ->map(fn($v) => (int) $v)
        ->all();
}

/**
 * Get all notifications by content key.
 * Returns a collection of stdClass objects.
 * @return \Illuminate\Support\Collection
 */
function getAllNotificationsByKey(string $contentKey): \Illuminate\Support\Collection
{
    return DB::table('notifications')
        ->where('content_key', $contentKey)
        ->orderByDesc('id')
        ->get();
}

/**
 * Count notifications by content key using the public API.
 */
function countNotificationsByKey(string $contentKey): int
{
    /** @var NotificationPublicApi $api */
    $api = app(NotificationPublicApi::class);
    return $api->countNotificationsByType($contentKey);
}
