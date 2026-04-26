# Discord Notifications - Feature Planning

## Overview

The Discord notification feature lets users receive website notifications as Discord DMs. It is built as an **external notification channel** registered into the Notification domain's channel registry. The Discord domain depends on the Notification domain — never the reverse.

This document covers the server-side architecture. The bot-facing HTTP API is described in [Discord_Api_Usage.md](Discord_Api_Usage.md).

---

## Functional Summary

- Users who have linked their Discord account and opted in for a given notification type + Discord channel receive a DM when that notification fires.
- Opt-in preferences are stored in the Notification domain's `notification_preferences` table under `channel = 'discord'`. Default is **OFF** — users must explicitly opt in.
- The Discord bot polls `GET /api/discord/notifications/pending` periodically, sends DMs, then marks them sent via `POST /api/discord/notifications/mark-sent`.
- If a user has no Discord account linked, they are silently skipped even if they have opted in (edge case; the opt-in UI should guide users to link first).
- If a user disconnects their Discord account, all their pending unsent Discord notifications are deleted.
- This feature is gated behind the `features.discord_notifications` config flag. When off, the Discord column is hidden from the notification preferences page and the channel's delivery callback is never called.

---

## Architecture

### Dependency Direction

```
Discord domain → Notification domain   ✓
Notification domain → Discord domain   ✗ (never)
```

The Discord domain:
- Registers itself as a notification channel at boot.
- Owns the `discord_pending_notifications` table.
- Exposes the bot API endpoints.

The Notification domain:
- Has no knowledge of Discord.
- Calls the registered callback at dispatch time.
- Stores Discord opt-in preferences in `notification_preferences(channel='discord')`.

### Channel Registration

In `DiscordServiceProvider::boot()`:

```php
$channelRegistry = app(NotificationChannelRegistry::class);

$channelRegistry->register(new NotificationChannelDefinition(
    id: 'discord',
    nameKey: 'discord::channels.name',
    defaultEnabled: false,
    sortOrder: 20,
    deliveryCallback: function (Notification $notification, array $userIds) {
        app(DiscordNotificationQueueService::class)->queue($notification, $userIds);
    },
    featureFlag: 'features.discord_notifications',
));
```

The callback is a thin closure that resolves `DiscordNotificationQueueService` lazily from the container — safe to register at boot time.

---

## Data Model

One notification can have many recipients. Storing one row per recipient would duplicate the notification content in every API response. Instead, two tables separate the notification entry from the per-recipient delivery status.

### `discord_pending_notifications` Table

One row per notification that has at least one Discord recipient.

```php
Schema::create('discord_pending_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
    $table->timestamps();

    $table->index('created_at'); // Bot poll ordering
});
```

### `discord_pending_recipients` Table

One row per (pending_notification, discord user). Tracks individual delivery status.

```php
Schema::create('discord_pending_recipients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pending_notification_id')
          ->constrained('discord_pending_notifications')
          ->onDelete('cascade');
    $table->unsignedBigInteger('user_id');  // Website user ID — for cleanup on disconnect
    $table->string('discord_id', 20);       // Discord snowflake ID — for DM delivery
    $table->timestamp('sent_at')->nullable(); // null = pending, timestamp = delivered
    $table->timestamps();

    $table->index(['pending_notification_id', 'sent_at']); // Poll: unsent recipients per notification
    $table->index('user_id');                               // Cleanup on disconnect
});
```

**Design notes:**
- `notification_id` on the parent table cascades on delete — if the Notification cleanup job removes old notifications, all associated Discord rows are also removed.
- `discord_id` is resolved at queue time (intra-domain lookup from the Discord user table).
- `sent_at = null` means pending. The bot marks recipients delivered via the mark-sent endpoint.
- Notification content is **not stored** in either table — it is fetched at poll time from the Notification domain via `NotificationPublicApi`.
- When a user disconnects Discord, their `discord_pending_recipients` rows are deleted. Any `discord_pending_notifications` row with no remaining unsent recipients is effectively inert and will be cleaned up by cascade when the parent notification expires.

---

## Key Services

### `DiscordNotificationQueueService`

Owns the delivery callback logic. Called by the Notification domain's dispatch with `(Notification $notification, array $userIds)`.

```php
class DiscordNotificationQueueService
{
    public function queue(Notification $notification, array $userIds): void
    {
        $recipients = [];
        foreach ($userIds as $userId) {
            $discordId = $this->discordUserRepository->findDiscordId($userId);
            if ($discordId === null) {
                continue; // User has no linked Discord account — skip silently
            }
            $recipients[] = ['user_id' => $userId, 'discord_id' => $discordId];
        }

        if (empty($recipients)) {
            return;
        }

        $pending = $this->repository->createPending($notification->id);
        $this->repository->createRecipients($pending->id, $recipients);
    }
}
```

### `DiscordNotificationApiController`

Handles the bot-facing REST endpoints. Lives in `Discord/Private/Controllers/`.

```php
// GET /api/discord/notifications/pending
public function pending(Request $request): JsonResponse
// Returns paginated pending notifications, each with a recipients list (discord_ids only).
// Content is fetched per notification via NotificationPublicApi::getNotificationData().

// POST /api/discord/notifications/mark-sent
public function markSent(Request $request): JsonResponse
// For each entry: if failedRecipients is absent, marks all recipients of that notification sent.
// If failedRecipients is present, marks all recipients EXCEPT those discord_ids as sent.
// Failed recipients remain pending and will reappear on the next poll.
```

The `pending` action fetches `discord_pending_notifications` that have at least one recipient with `sent_at IS NULL`, ordered by `created_at`. For each, it calls `NotificationPublicApi::getNotificationData($notificationId)` and `NotificationFactory::make(type, data)` to build the `data` payload.

### `DiscordPendingNotificationRepository`

```php
public function getPendingWithRecipients(int $perPage, int $page): LengthAwarePaginator;
// Returns pending_notifications that have ≥1 unsent recipient, with their discord_ids eager-loaded

public function createPending(int $notificationId): DiscordPendingNotification;
public function createRecipients(int $pendingId, array $recipients): void;
// $recipients = [['user_id' => int, 'discord_id' => string], ...]

public function markAllRecipientsDelivered(int $pendingNotificationId): int;
// Sets sent_at = now() for all unsent recipients of this pending notification

public function markRecipientsDeliveredExcept(int $pendingNotificationId, array $failedDiscordIds): int;
// Sets sent_at = now() for all unsent recipients except the given discord_ids

public function deleteRecipientsForUser(int $userId): void;
// Called on Discord disconnect — removes all pending recipient rows for a website user
```

---

## Discord Preferences

Discord opt-in preferences are stored in the Notification domain's `notification_preferences` table with `channel = 'discord'` and `enabled = true` (since the default for this channel is `false`, only opted-in rows are stored).

The Notification preferences page (custom Settings tab owned by Notification domain) automatically shows the Discord column when the `features.discord_notifications` flag is on, because `NotificationChannelRegistry::getActiveChannels()` includes the Discord channel.

Users toggle Discord preferences through the standard `PUT /notifications/preferences/{type}` route (Notification domain controller). The Discord domain has no preferences controller of its own.

**Note for the UI**: If a user has opted in to Discord notifications but has no Discord account linked, they will not receive DMs (silently skipped in `DiscordNotificationQueueService::queue()`). The preferences page should display a warning in the Discord column header if the user has no Discord account linked (the Discord domain can expose a public API method `isLinked(int $userId): bool` for this).

---

## Notification Content for Discord

The bot response format (`{message, url, actor, target}`) is built from `NotificationContent` instances. The `NotificationContent` interface should expose:

```php
interface NotificationContent
{
    public static function fromData(array $data): static;
    public function render(): string;          // Website HTML rendering (existing)
    public function getUrl(): string;          // Canonical URL for the subject
    public function getActorName(): ?string;   // Who triggered the event (null for system)
    public function getTargetDescription(): string; // What was affected
}
```

Each notification content class (in each domain) implements these methods. The `DiscordNotificationApiController` calls `$content->render()` (plain text version, stripped of HTML tags) and the additional methods to build the `data` payload.

---

## Domain Structure

```
Discord/
├── Public/
│   └── Providers/
│       └── DiscordServiceProvider.php               (UPDATED: register channel)
├── Private/
│   ├── Controllers/
│   │   └── DiscordNotificationApiController.php     (NEW)
│   ├── Models/
│   │   ├── DiscordPendingNotification.php            (NEW)
│   │   └── DiscordPendingRecipient.php               (NEW)
│   ├── Repositories/
│   │   └── DiscordPendingNotificationRepository.php  (NEW)
│   ├── Services/
│   │   └── DiscordNotificationQueueService.php       (NEW)
│   └── routes.php                                    (UPDATED: add notification API routes)
└── Database/Migrations/
    ├── xxxx_create_discord_pending_notifications_table.php (NEW)
    └── xxxx_create_discord_pending_recipients_table.php    (NEW)
```

---

## Flow Summary

```
1. A notification is triggered (comment posted, news published, etc.)
          ↓
2. NotificationPublicApi::createNotification() runs
          ↓
3. Website channel: notification_reads rows created for opted-in website users
          ↓
4. Discord channel callback called with opted-in Discord users
          ↓
5. DiscordNotificationQueueService::queue() resolves discord_ids, inserts into discord_pending_notifications
          ↓
6. Discord bot polls GET /api/discord/notifications/pending (every ~1 minute)
          ↓
7. Bot sends DMs, calls POST /api/discord/notifications/mark-sent
          ↓
8. sent_at is set, row remains until cascaded cleanup (notification deleted after 1 month)
```

---

## Implementation Steps

### Phase 0: Prerequisite
- Notification Preferences feature must be implemented first (channel registry must exist).

### Phase 1: Queue Infrastructure
1. Create `discord_pending_notifications` + `discord_pending_recipients` migrations and models
2. Implement `DiscordPendingNotificationRepository`
3. Implement `DiscordNotificationQueueService`

### Phase 2: Channel Registration
4. Update `DiscordServiceProvider::boot()` to register the `discord` channel with `NotificationChannelRegistry`

### Phase 3: Bot API
5. Implement `DiscordNotificationApiController` (`pending`, `markSent`)
6. Register new API routes in `Discord/Private/routes.php`
7. Update `NotificationContent` interface with `getUrl()`, `getActorName()`, `getTargetDescription()`
8. Implement these methods in all existing `NotificationContent` classes

### Phase 4: Disconnect Cleanup
9. Update Discord disconnect handler to call `DiscordPendingNotificationRepository::deleteRecipientsForUser()`

### Phase 5: Preferences UI hint
10. Expose `DiscordPublicApi::isLinked(int $userId): bool`
11. Update the Notification preferences Blade view to show a warning in the Discord column header if `!$discordApi->isLinked(auth()->id())`

### Phase 6: Testing
12. Channel registration: Discord channel appears in `getActiveChannels()` when flag on; absent when flag off
13. Queue: `DiscordNotificationQueueService::queue()` creates one pending_notification + N recipient rows; skips users with no discord_id; creates no pending_notification when all users are skipped
14. Bot API: `pending` returns notifications grouped with recipients list; content rendered correctly; `markSent` with no `failedRecipients` marks all recipients delivered; `markSent` with `failedRecipients` leaves those recipients pending; authentication required
15. Disconnect: `deleteRecipientsForUser()` clears only that user's recipient rows; sibling recipients unaffected
16. Cascade: deleting a `notifications` row cascades to `discord_pending_notifications` and `discord_pending_recipients`
17. End-to-end: creating a notification for a discord-opted-in user results in one pending_notification + one recipient row; broadcast creates one pending_notification + N recipient rows for opted-in users only
