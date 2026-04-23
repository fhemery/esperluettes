# Notification Domain — Agent Instructions

- README: [app/Domains/Notification/README.md](README.md)

## Public API

- [NotificationPublicApi](Public/Api/NotificationPublicApi.php) — `createNotification`, `createBroadcastNotification`, `getUnreadCount`, `deleteNotificationsByType`, `countNotificationsByType`
- [NotificationFactory](Public/Services/NotificationFactory.php) — singleton registry; `register`, `resolve`, `make`, `getRegisteredTypes`
- [NotificationContent](Public/Contracts/NotificationContent.php) — interface that every notification payload must implement

## Events emitted

| Event | When |
|-------|------|
| `NotificationsCleanedUp` | After `notifications:cleanup` artisan command completes |

## Listens to

| Event | Action |
|-------|--------|
| `Auth::UserDeleted` | Removes all `notification_reads` rows for that user; removes all notifications where that user was `source_user_id` |

## Non-obvious invariants

**Type keys are permanent.** Once a `NotificationContent::type()` string has been stored in the database, it must never change. The factory resolves stored rows by this key at render time. An unrecognized key causes the notification to be silently discarded on display and deleted by the next cleanup run.

**No FK constraints on user columns.** Neither `notifications.source_user_id` nor `notification_reads.user_id` has a foreign key to `users`. Cross-domain FK constraints are prohibited. Cleanup on user deletion is performed exclusively by the `CleanNotificationsOnUserDeleted` listener.

**`notification_reads` uses a composite primary key** `(notification_id, user_id)`. The `NotificationRead` model sets `$incrementing = false` and `$primaryKey = null`. Do not attempt to use Eloquent `find()` on this model — query via `DB::table()` directly, as the service layer does.

**Cascade delete from `notifications` to `notification_reads`.** The `notification_reads.notification_id` column has `ON DELETE CASCADE`. Deleting a notification row (by type, by age, or by source user) automatically removes all its read-tracking rows.

**System vs. user-sourced notifications.** When `source_user_id` is null the notification is treated as a system notification. The view model sets `is_system = true` accordingly; the blade template renders it differently (no avatar).

**`NotificationPageViewModel` silently drops unknown types.** If `NotificationFactory::make()` returns null for a stored row, that row is filtered out before the view renders. This is intentional — stale types from deregistered domains produce no visible error.

**`loadMore` returns raw HTML, not JSON.** The endpoint renders each `notification-item` component server-side and concatenates the HTML strings. Callers must read the `X-Has-More` response header (string `"true"` / `"false"`) to decide whether to show the load-more button.

**Mark-as-read and mark-as-unread are idempotent.** Both operations check the current state before writing. Calling them on a notification that does not belong to the authenticated user is silently ignored (no error, no change).

**Cleanup deletes in two passes.** `notifications:cleanup` first deletes rows older than 30 days, then deletes rows whose `content_key` is not in `NotificationFactory::getRegisteredTypes()`. Order matters: the factory registry must be fully populated at command runtime (all service providers booted) for the second pass to be accurate.

## Registering a new notification type

Add to your domain `ServiceProvider::boot()`:

```php
app(\App\Domains\Notification\Public\Services\NotificationFactory::class)->register(
    type: YourNotification::type(),
    class: YourNotification::class
);
```

Place the class in `Public/Notifications/` within your domain. It must implement `NotificationContent`. Use `readonly` properties and keep `toData()` JSON-safe (no models).
