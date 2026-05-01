# Notification Domain

## Purpose

Centralize user-facing notifications across the platform. Provides a stable extensibility contract (`NotificationContent`) for domain-specific payloads that are persisted, reconstructed, and rendered for display. Exposes a public API to create targeted and broadcast notifications with validation and backfilling support. Supports per-user, per-type, per-channel delivery preferences via an extensible channel registry.

Feature planning doc: [docs/Feature_Planning/Notification_Preferences.md](../../../docs/Feature_Planning/Notification_Preferences.md) (may be partially outdated once implemented).

## Key concepts

### `NotificationContent` contract

`App\Domains\Notification\Public\Contracts\NotificationContent`

The interface all notification content types must implement:

| Method | Description |
|--------|-------------|
| `static type(): string` | Unique, stable type key (e.g. `story.chapter.root_comment`). Never change after creation. |
| `toData(): array` | Serialize to a JSON-safe array (scalars, arrays, IDs only — no Eloquent models). |
| `static fromData(array $data): static` | Reconstruct the object from stored data. |
| `display(): string` | Return localized HTML for display in the UI. |

Implementations should use `readonly` properties to ensure deterministic serialization.

### `NotificationFactory` (singleton)

`App\Domains\Notification\Public\Services\NotificationFactory`

A singleton registry that maps type strings to their implementation classes. Types are organized into groups for display in the preferences UI.

**Group management:**

| Method | Description |
|--------|-------------|
| `registerGroup(string $id, int $sortOrder, string $translationKey)` | Register a notification group. Groups must be registered before any type in that group. |
| `getGroups(): array` | Return all groups sorted by `sortOrder`. |
| `getTypesForGroup(string $groupId, bool $includeHidden = false): array` | Return types for a group; hidden types excluded by default. |

**Type management:**

| Method | Description |
|--------|-------------|
| `register(string $type, string $class, string $groupId, string $nameKey, bool $forcedOnWebsite = false, bool $hideInSettings = false)` | Register a `NotificationContent` class. Throws if `groupId` is not registered. |
| `getTypeDefinition(string $type): ?NotificationTypeDefinition` | Return the full type definition, or null if unknown. |
| `resolve(string $type): ?string` | Return the class name for a type, or null if unregistered. |
| `make(string $type, array $data): ?NotificationContent` | Instantiate content via `fromData()`, or null if unregistered. |
| `getRegisteredTypes(): array` | List all registered type keys. |

Pre-defined groups (registered in `NotificationServiceProvider`, in `NotificationServiceProvider::boot()`):

| Group ID | Sort | Purpose |
|----------|------|---------|
| `comments` | 10 | Comment-related notifications |
| `collaboration` | 20 | Story collaboration notifications |
| `readlist` | 30 | Reading list notifications |
| `news` | 40 | Site news notifications |
| `moderation` | 50 | Moderation and admin notifications |

### `NotificationPublicApi`

`App\Domains\Notification\Public\Api\NotificationPublicApi`

The primary entry point for other domains:

| Method | Description |
|--------|-------------|
| `createNotification(int[] $userIds, NotificationContent $content, ?int $sourceUserId = null, ?\DateTime $createdAt = null)` | Validate and persist a notification for specific users with channel-aware delivery. |
| `createBroadcastNotification(NotificationContent $content, ?int $sourceUserId = null)` | Send to all users with roles `user` and `user-confirmed` with channel-aware delivery. |
| `getUnreadCount(int $userId): int` | Count unread notifications for a user. |
| `deleteNotificationsByType(string $contentKey): int` | Delete all notifications of a given type (returns count deleted). |
| `countNotificationsByType(string $contentKey): int` | Count notifications of a given type. |

Validation rules applied by `createNotification`:
- `userIds` must be non-empty and all must be existing users (validated via `ProfilePublicApi`).
- `sourceUserId`, if provided, must also be an existing user.
- IDs are deduplicated and cast to int automatically.

### Delivery Channels

A **channel** is a delivery mechanism. The `website` channel is native — it creates `notification_reads` rows and is always present. All other channels (e.g., Discord) are registered externally via `NotificationChannelRegistry`.

`App\Domains\Notification\Public\Services\NotificationChannelRegistry`

External domains register a channel in their `ServiceProvider::boot()`:

```php
app(NotificationChannelRegistry::class)->register(new NotificationChannelDefinition(
    id: 'discord',
    nameTranslationKey: 'discord::notifications.channel_name',
    defaultEnabled: false,
    sortOrder: 10,
    deliveryCallback: function(NotificationDto $dto, array $userIds): void {
        // $dto->id, $dto->type, $dto->data are available
    },
    featureFlag: 'services.discord.enabled', // optional; channel hidden when flag is off
));
```

The `'website'` ID is reserved — registering it throws `InvalidArgumentException`. At dispatch time, the Notification domain filters each user list per channel preferences and calls the delivery callback only if the filtered list is non-empty. Channels whose feature flag is off are skipped entirely.

### User Preferences

Users control which notification types they receive, per channel, through the **Notifications** tab in the Settings page. This tab uses the custom-view capability of the Settings domain: the Notification domain registers a `SettingsTabDefinition` with `customViewPath: 'notification::settings.settings'`, and the Settings domain delegates content rendering to that view.

The preferences model uses sparse storage: only non-default values are persisted in `notification_preferences`. When a user sets a preference equal to the channel's `defaultEnabled`, the row is deleted rather than saved. This keeps the table lean and ensures future default changes don't require migrations.

**Preference filtering happens at write time.** When `createNotification()` or `createBroadcastNotification()` is called, the domain computes which users are eligible per channel before creating `notification_reads` rows or calling delivery callbacks. Existing rows are never retroactively removed when preferences change.

**`forcedOnWebsite`** types cannot be opted out on the website channel. The website toggle is rendered as disabled in the preferences UI. Other channels remain fully user-controlled even for forced types. This flag is enforced in `NotificationPublicApi` at dispatch time and in `NotificationPreferencesController` at preference update time.

**`hideInSettings`** types are excluded from the preferences UI and from `getTypesForGroup()` by default. Used for deprecated or legacy type keys (e.g., `story.chapter.comment` which was superseded by `story.chapter.root_comment` and `story.chapter.reply_comment`). These types are still delivered normally — `hideInSettings` only affects the preferences page.

## Database schema

### `notifications`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-increment |
| `source_user_id` | integer, nullable | The acting user (no FK constraint — cross-domain). Null for system notifications. |
| `content_key` | string | Type identifier (e.g. `story.chapter.root_comment`) |
| `content_data` | json | Serialized payload from `toData()` |
| `created_at` / `updated_at` | timestamps | |

Index on `created_at` for cleanup queries.

### `notification_reads`

| Column | Type | Notes |
|--------|------|-------|
| `notification_id` | bigint FK → `notifications(id)` ON DELETE CASCADE | Composite PK |
| `user_id` | integer | No FK constraint (cross-domain). Composite PK. |
| `read_at` | timestamp, nullable | Null means unread |
| `created_at` / `updated_at` | timestamps | |

Composite index on `(user_id, read_at)` for unread count queries.

### `notification_preferences`

Sparse storage of non-default preferences. Only rows that differ from the channel's `defaultEnabled` are stored.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | integer | No FK constraint (cross-domain) |
| `type` | string(100) | Notification type key |
| `channel` | string(50) | Channel ID (`website`, `discord`, etc.) |
| `enabled` | boolean | |
| `created_at` / `updated_at` | timestamps | |

Unique constraint on `(user_id, type, channel)`. Indexes on `user_id` and `(type, channel, enabled)`.

## HTTP routes

All routes require `auth` + `compliant` middleware, and the `user` or `user-confirmed` role.

**Notifications:**

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/notifications` | `notifications.index` | Notification list page (paginated, 20/page) |
| GET | `/notifications/load-more` | `notifications.loadMore` | Load next page (returns rendered HTML + `X-Has-More` header) |
| POST | `/notifications/{id}/read` | `notifications.markRead` | Mark one as read (idempotent, 204) |
| POST | `/notifications/{id}/unread` | `notifications.markUnread` | Mark one as unread (idempotent, 204) |
| POST | `/notifications/read-all` | `notifications.markAllRead` | Mark all as read (204) |

**Preferences:**

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| POST | `/notifications/preferences` | `notification.preferences.save` | Save a single preference (type + channel + enabled) |
| PUT | `/notifications/preferences/{type}` | `notification.preferences.update` | Update a single type toggle |
| PUT | `/notifications/preferences` | `notification.preferences.bulk` | Bulk update (global or by group) |

The `loadMore` endpoint returns raw rendered HTML for each notification item and sets the `X-Has-More: true/false` response header.

## Blade components

- `<x-notification::notification-icon />` — Bell icon for the navbar showing the unread count. Self-contained: fetches the count in its constructor. Hidden for guests.
- `notification::components.notification-item` — Partial used for each notification row (also returned by `loadMore`).

## Artisan command

`notifications:cleanup`

Runs two cleanup operations:
1. Deletes notifications older than 30 days.
2. Deletes notifications whose `content_key` is not registered in `NotificationFactory` (stale/orphaned types).

Emits a `NotificationsCleanedUp` domain event via `EventBus` after completing.

## Events

### Emitted

| Event | When |
|-------|------|
| `NotificationsCleanedUp` | After `notifications:cleanup` command runs |

### Listens to

| Event | Action |
|-------|--------|
| `Auth::UserDeleted` | Deletes all `notification_reads` rows for that user; deletes all notifications where that user was the `source_user_id` |

## Registration of notification types

Each domain that defines notification content must register its groups and types in its own `ServiceProvider::boot()`. Groups must already exist (the Notification domain registers them in its own provider, which boots first).

```php
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\MyDomain\Public\Notifications\SomethingHappenedNotification;

public function boot(): void
{
    app(NotificationFactory::class)->register(
        type: SomethingHappenedNotification::type(),
        class: SomethingHappenedNotification::class,
        groupId: 'comments',           // must be a pre-registered group
        nameKey: 'mydomain::notifications.something_happened_label',
        forcedOnWebsite: false,        // optional
        hideInSettings: false,         // optional; true for deprecated/legacy types
    );
}
```

## Implementing a new notification type

**Step 1 — Create the class** in `Public/Notifications/` of your domain:

```php
final class SomethingHappenedNotification implements NotificationContent
{
    public function __construct(
        public readonly int $entityId,
        public readonly string $actorName,
    ) {}

    public static function type(): string
    {
        return 'mydomain.something.happened';
    }

    public function toData(): array
    {
        return [
            'entity_id' => $this->entityId,
            'actor_name' => $this->actorName,
        ];
    }

    public static function fromData(array $data): static
    {
        return new static(
            entityId: (int) ($data['entity_id'] ?? 0),
            actorName: (string) ($data['actor_name'] ?? ''),
        );
    }

    public function display(): string
    {
        return __('mydomain::notifications.something_happened', [
            'actor_name' => e($this->actorName),
        ]);
    }
}
```

**Step 2 — Register the type** in your `ServiceProvider::boot()` (see above).

**Step 3 — Produce notifications** via `NotificationPublicApi`:

```php
// Targeted (in-domain service or cross-domain listener)
app(NotificationPublicApi::class)->createNotification(
    userIds: [$authorId],
    content: new SomethingHappenedNotification($entityId, $actorName),
    sourceUserId: $actorId,
);

// Broadcast to all registered users
app(NotificationPublicApi::class)->createBroadcastNotification(
    content: new SomethingHappenedNotification($entityId, $actorName),
);
```

## Validation and constraints

- `toData()` must return a JSON-safe array — no Eloquent models, no closures.
- Type strings must be stable. Changing a type key after data has been stored will orphan existing rows (unresolvable by `NotificationFactory`), causing them to be silently discarded on display and deleted by the next cleanup run.
- There are no foreign key constraints from `notifications.source_user_id` or `notification_reads.user_id` or `notification_preferences.user_id` to the `users` table (cross-domain FK prohibition). Cleanup on user deletion is handled by the `CleanNotificationsOnUserDeleted` listener.

## Backfilling historical notifications

Pass a `\DateTime` as `$createdAt` to `createNotification()` to store a custom timestamp instead of `now()`:

```php
$api->createNotification(
    userIds: $userIds,
    content: $content,
    sourceUserId: $actorId,
    createdAt: new \DateTime('2025-01-01 12:00:00'),
);
```

## i18n

- Translations namespace: `notifications` (loaded from `Private/Resources/lang/`).
- Notification content `display()` methods use their own domain's translation namespace (e.g. `readlist::notifications.*`, `story::notifications.*`).
- Use `e()` to escape interpolated strings in `display()` output to prevent XSS.

## Testing utilities

`Tests/helpers.php` provides global test helper functions (auto-loaded in the test environment):

| Function | Description |
|----------|-------------|
| `makeNotification(array $userIds, ...): int` | Create a notification via the public API; returns its ID |
| `notificationReadRow(int $userId, int $notificationId): ?object` | Fetch a `notification_reads` row |
| `markNotificationAsRead(TestCase, int)` | POST mark-as-read and assert 204 |
| `markNotificationAsUnread(TestCase, int)` | POST mark-as-unread and assert 204 |
| `markAllNotificationsAsRead(TestCase)` | POST mark-all-read and assert 204 |
| `getLatestNotificationByKey(string): ?Notification` | Fetch most recent notification by type |
| `getNotificationTargetUserIds(int): array` | Fetch user IDs from `notification_reads` for a notification |
| `getAllNotificationsByKey(string): Collection` | All notifications with a given type key |
| `countNotificationsByKey(string): int` | Count via public API |
| `notificationExists(int): bool` | Check if a notification row exists by ID |

`Tests/Fixtures/TestNotificationContent` is a minimal `NotificationContent` implementation (`type: test.notification`) for use in tests. `Tests/Fixtures/ForcedTestNotificationContent` is a variant with `forcedOnWebsite: true`.
