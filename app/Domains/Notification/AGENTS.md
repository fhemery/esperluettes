# Notification Domain — Agent Instructions

- README: [app/Domains/Notification/README.md](README.md)

## Public API

- [NotificationPublicApi](Public/Api/NotificationPublicApi.php) — `createNotification`, `createBroadcastNotification`, `getUnreadCount`, `deleteNotificationsByType`, `countNotificationsByType`
- [NotificationFactory](Public/Services/NotificationFactory.php) — singleton registry; `registerGroup`, `register`, `resolve`, `make`, `getRegisteredTypes`, `getGroups`, `getTypesForGroup`, `getTypeDefinition`
- [NotificationChannelRegistry](Public/Services/NotificationChannelRegistry.php) — singleton registry for external delivery channels; `register`, `get`, `getActiveChannels`, `getAllChannels`
- [NotificationContent](Public/Contracts/NotificationContent.php) — interface that every notification payload must implement
- [NotificationChannelDefinition](Public/Contracts/NotificationChannelDefinition.php) — value object for channel registration
- [NotificationDto](Public/Contracts/NotificationDto.php) — value object passed to channel delivery callbacks (`id`, `type`, `data`)

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

**Groups must be registered before types.** `NotificationFactory::register()` throws `InvalidArgumentException` if the `groupId` has not been registered via `registerGroup()` first. The Notification domain registers all groups in its own `ServiceProvider::boot()` (which boots before other domains). Do not call `registerGroup()` from outside the Notification domain.

**`register()` now requires `groupId` and `nameKey`.** The old 2-argument signature (`type`, `class`) is gone. All registrations must pass `groupId`, `nameKey`, and optionally `forcedOnWebsite` and `hideInSettings`. Calling with the old signature causes a PHP error.

**`website` channel is native, not in the channel registry.** The `NotificationChannelRegistry` is for external channels only. The `website` channel is handled directly in the dispatch methods (creating `notification_reads` rows). Registering a channel with `id = 'website'` throws `InvalidArgumentException`.

**`forcedOnWebsite` applies to the website channel only.** The flag means "this type cannot be opted out on the website." It has no bearing on other channels — those remain fully user-controlled even for forced types. Enforced in `NotificationPublicApi` at dispatch time and in `NotificationPreferencesController` at preference update time.

**`hideInSettings` does not affect delivery.** Types with `hideInSettings: true` are still dispatched normally; the flag only excludes them from the preferences UI (and from `getTypesForGroup()` when called without `includeHidden: true`). Used for legacy type keys that have been superseded.

**Preferences are sparse.** The `notification_preferences` table only stores rows that differ from the channel's `defaultEnabled`. When a user sets a preference equal to the default, the row is deleted. Filtering at dispatch time accounts for this (see `NotificationPreferencesRepository::filterForChannel`).

**No FK constraints on user columns.** Neither `notifications.source_user_id`, `notification_reads.user_id`, nor `notification_preferences.user_id` has a foreign key to `users`. Cross-domain FK constraints are prohibited. Cleanup on user deletion is performed exclusively by the `CleanNotificationsOnUserDeleted` listener.

**`notification_reads` uses a composite primary key** `(notification_id, user_id)`. The `NotificationRead` model sets `$incrementing = false` and `$primaryKey = null`. Do not attempt to use Eloquent `find()` on this model — query via `DB::table()` directly, as the service layer does.

**Cascade delete from `notifications` to `notification_reads`.** The `notification_reads.notification_id` column has `ON DELETE CASCADE`. Deleting a notification row (by type, by age, or by source user) automatically removes all its read-tracking rows.

**System vs. user-sourced notifications.** When `source_user_id` is null the notification is treated as a system notification. The view model sets `is_system = true` accordingly; the blade template renders it differently (no avatar).

**`NotificationPageViewModel` silently drops unknown types.** If `NotificationFactory::make()` returns null for a stored row, that row is filtered out before the view renders. This is intentional — stale types from deregistered domains produce no visible error.

**`loadMore` returns raw HTML, not JSON.** The endpoint renders each `notification-item` component server-side and concatenates the HTML strings. Callers must read the `X-Has-More` response header (string `"true"` / `"false"`) to decide whether to show the load-more button.

**Mark-as-read and mark-as-unread are idempotent.** Both operations check the current state before writing. Calling them on a notification that does not belong to the authenticated user is silently ignored (no error, no change).

**Cleanup deletes in two passes.** `notifications:cleanup` first deletes rows older than 30 days, then deletes rows whose `content_key` is not in `NotificationFactory::getRegisteredTypes()`. Order matters: the factory registry must be fully populated at command runtime (all service providers booted) for the second pass to be accurate.

**Channel delivery callbacks receive `NotificationDto`, not the `Notification` model.** The callback signature is `fn(NotificationDto $dto, array $userIds): void`. The `NotificationDto` carries `id`, `type`, and `data` (the serialized payload). Do not type-hint `Notification` (the Eloquent model) in delivery callbacks.

**Broadcast on default-OFF channels queries opted-in users directly.** For channels with `defaultEnabled: false`, `createBroadcastNotification` calls `getOptedInUserIds(type, channel)` instead of filtering the full user list. This avoids a massive IN clause. For default-ON channels, it filters the full user list using `filterForChannel`.

## Registering a new notification type

Add to your domain `ServiceProvider::boot()`:

```php
app(\App\Domains\Notification\Public\Services\NotificationFactory::class)->register(
    type: YourNotification::type(),
    class: YourNotification::class,
    groupId: 'comments',            // must be a group pre-registered by the Notification domain
    nameKey: 'mydomain::notifications.your_type_label',
    forcedOnWebsite: false,         // optional; true = user cannot opt out on website
    hideInSettings: false,          // optional; true = excluded from preferences UI
);
```

Place the class in `Public/Notifications/` within your domain. It must implement `NotificationContent`. Use `readonly` properties and keep `toData()` JSON-safe (no models).

## Registering a new delivery channel

Add to your domain `ServiceProvider::boot()`:

```php
app(\App\Domains\Notification\Public\Services\NotificationChannelRegistry::class)->register(
    new \App\Domains\Notification\Public\Contracts\NotificationChannelDefinition(
        id: 'my_channel',
        nameTranslationKey: 'mydomain::notifications.channel_name',
        defaultEnabled: false,
        sortOrder: 10,
        deliveryCallback: function(\App\Domains\Notification\Public\Contracts\NotificationDto $dto, array $userIds): void {
            // deliver to $userIds; $dto->type and $dto->data are available
        },
        featureFlag: 'services.my_channel.enabled', // optional
    )
);
```

The `'website'` ID is reserved — using it throws `InvalidArgumentException`. Duplicate IDs also throw.
