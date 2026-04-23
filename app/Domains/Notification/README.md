# Notification Domain

## Purpose

Centralize user-facing notifications across the platform. Provides a stable extensibility contract (`NotificationContent`) for domain-specific payloads that are persisted, reconstructed, and rendered for display. Exposes a public API to create targeted and broadcast notifications with validation and backfilling support.

## Architecture overview

```
Notification/
  Database/
    Migrations/               notifications, notification_reads tables
  Private/
    Console/                  CleanupOldNotificationsCommand
    Controllers/              NotificationController
    Listeners/                CleanNotificationsOnUserDeleted
    Models/                   Notification, NotificationRead
    Resources/
      lang/fr/                pages.php, events.php
      views/
        components/           notification-icon.blade.php, notification-item.blade.php
        pages/                index.blade.php
    Services/                 NotificationService
    View/Components/          NotificationIconComponent
    ViewModels/               NotificationViewModel, NotificationPageViewModel
    routes.php
  Public/
    Api/                      NotificationPublicApi
    Contracts/                NotificationContent (interface)
    Events/                   NotificationsCleanedUp
    Providers/                NotificationServiceProvider
    Services/                 NotificationFactory (singleton)
  Tests/
    Feature/                  Full feature test coverage
    Fixtures/                 TestNotificationContent
    helpers.php               Test utility functions
```

## Key concepts

### `NotificationContent` contract

`App\Domains\Notification\Public\Contracts\NotificationContent`

The interface all notification content types must implement:

| Method | Description |
|--------|-------------|
| `static type(): string` | Unique, stable type key (e.g. `story.chapter.comment`). Never change after creation. |
| `toData(): array` | Serialize to a JSON-safe array (scalars, arrays, IDs only — no Eloquent models). |
| `static fromData(array $data): static` | Reconstruct the object from stored data. |
| `display(): string` | Return localized HTML for display in the UI. |

Implementations should use `readonly` properties to ensure deterministic serialization.

### `NotificationFactory` (singleton)

`App\Domains\Notification\Public\Services\NotificationFactory`

A singleton registry that maps type strings to their implementation classes:

| Method | Description |
|--------|-------------|
| `register(string $type, string $class)` | Register a `NotificationContent` class for a type key. Call from domain `ServiceProvider::boot()`. |
| `resolve(string $type): ?string` | Return the class name for a type, or null if unknown. |
| `make(string $type, array $data): ?NotificationContent` | Instantiate content via `fromData()`, or null if unregistered. |
| `getRegisteredTypes(): array` | List all registered type keys. |

### `NotificationPublicApi`

`App\Domains\Notification\Public\Api\NotificationPublicApi`

The primary entry point for other domains:

| Method | Description |
|--------|-------------|
| `createNotification(int[] $userIds, NotificationContent $content, ?int $sourceUserId = null, ?\DateTime $createdAt = null)` | Validate and persist a notification for specific users. |
| `createBroadcastNotification(NotificationContent $content, ?int $sourceUserId = null)` | Send to all users with roles `user` and `user-confirmed`. |
| `getUnreadCount(int $userId): int` | Count unread notifications for a user. |
| `deleteNotificationsByType(string $contentKey): int` | Delete all notifications of a given type (returns count deleted). |
| `countNotificationsByType(string $contentKey): int` | Count notifications of a given type. |

Validation rules applied by `createNotification`:
- `userIds` must be non-empty and all must be existing users (validated via `ProfilePublicApi`).
- `sourceUserId`, if provided, must also be an existing user.
- IDs are deduplicated and cast to int automatically.

## Database schema

### `notifications`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | Auto-increment |
| `source_user_id` | integer, nullable | The acting user (no FK constraint — cross-domain). Null for system notifications. |
| `content_key` | string | Type identifier (e.g. `story.chapter.comment`) |
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

## HTTP routes

All routes require `auth` + `compliant` middleware, and the `user` or `user-confirmed` role.

| Method | URI | Route name | Action |
|--------|-----|------------|--------|
| GET | `/notifications` | `notifications.index` | Notification list page (paginated, 20/page) |
| GET | `/notifications/load-more` | `notifications.loadMore` | Load next page (returns rendered HTML + `X-Has-More` header) |
| POST | `/notifications/{id}/read` | `notifications.markRead` | Mark one as read (idempotent, 204) |
| POST | `/notifications/{id}/unread` | `notifications.markUnread` | Mark one as unread (idempotent, 204) |
| POST | `/notifications/read-all` | `notifications.markAllRead` | Mark all as read (204) |

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

Each domain that defines notification content must register its types in its own `ServiceProvider::boot()`:

```php
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\MyDomain\Public\Notifications\SomethingHappenedNotification;

public function boot(): void
{
    app(NotificationFactory::class)->register(
        type: SomethingHappenedNotification::type(),
        class: SomethingHappenedNotification::class
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

## Known implementations

| Type key | Class | Domain |
|----------|-------|--------|
| `readlist.story.added` | `ReadListAddedNotification` | ReadList |
| `story.chapter.comment` | `ChapterCommentNotification` | Story |
| `story.coauthor.chapter_created` | `CoAuthorChapterCreatedNotification` | Story |
| `story.coauthor.chapter_updated` | `CoAuthorChapterUpdatedNotification` | Story |
| `story.coauthor.chapter_deleted` | `CoAuthorChapterDeletedNotification` | Story |
| `story.collaborator.role_given` | `CollaboratorRoleGivenNotification` | Story |
| `story.collaborator.role_removed` | `CollaboratorRoleRemovedNotification` | Story |
| `story.collaborator.left` | `CollaboratorLeftNotification` | Story |

## Validation and constraints

- `toData()` must return a JSON-safe array — no Eloquent models, no closures.
- Type strings must be stable. Changing a type key after data has been stored will orphan existing rows (unresolvable by `NotificationFactory`), causing them to be silently discarded on display and deleted by the next cleanup run.
- There are no foreign key constraints from `notifications.source_user_id` or `notification_reads.user_id` to the `users` table (cross-domain FK prohibition). Cleanup on user deletion is handled by the `CleanNotificationsOnUserDeleted` listener.

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

`Tests/Fixtures/TestNotificationContent` is a minimal `NotificationContent` implementation (`type: test.notification`) for use in tests.
