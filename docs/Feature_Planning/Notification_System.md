# Notification System - Feature Planning

## Overview

The Notification System allows users to be informed about events that concern them through an in-app notification center. Users will see a bell icon in the navigation menu with a badge indicating unread notifications.

## Functional Summary

### Notification Types (Phase 1)

1. **Comment Notifications**
   - Root comment on chapter → Notify all story authors
   - Reply to comment → Notify parent comment author

2. **News Notifications**
   - News posted → Notify all users

3. **JardiNo Notifications**
   - Flowers gained → Notify the participant

### User Experience

- Bell icon in navigation menu with unread count badge
- Clicking bell navigates to dedicated notifications page
- Page displays 10 notifications by default with "See more" button
- Each notification shows:
  - Icon (user avatar or site icon for system notifications)
  - Rich text with clickable links
  - Mark as read action (checkmark)
- Unread notifications displayed in bold
- "Mark all as read" button above notification list
- Clicking notification link opens in current tab

### Data Retention

- **All notifications**: 1 month (regardless of read status)
- Automatic cleanup via scheduled job
- Read status tracked separately per user

### Future Phases (Not in scope)

- **Phase 2**: User notification preferences (opt-out by type)
- **Phase 3**: Weekly email digests
- **Phase 4**: Discord API integration (external implementation)

---

## Technical Architecture

### Domain Structure

Create new **Notification** domain at `/app/Domains/Notification/`

```
Notification/
├── Public/
│   ├── Api/
│   │   └── NotificationPublicApi.php
│   ├── DTOs/
│   │   └── NotificationData.php
│   └── Renderers/
│       └── NotificationRenderer.php
├── Private/
│   ├── Models/
│   │   ├── Notification.php
│   │   └── NotificationRead.php
│   ├── Services/
│   │   └── NotificationService.php
│   ├── Repositories/
│   │   └── NotificationRepository.php
│   ├── Jobs/
│   │   └── CleanupOldNotificationsJob.php
│   ├── Policies/
│   │   └── NotificationReadPolicy.php
│   ├── Providers/
│   │   └── NotificationServiceProvider.php
│   ├── Resources/
│   │   └── views/
│   │       ├── index.blade.php
│   │       └── components/
│   │           └── notification-item.blade.php
│   └── routes.php
├── Database/
│   ├── Factories/
│   │   └── NotificationFactory.php
│   └── Migrations/
│       ├── YYYY_MM_DD_HHiiss_create_notifications_table.php
│       └── YYYY_MM_DD_HHiiss_create_notification_reads_table.php
└── Tests/
    ├── Feature/
    │   ├── NotificationPageTest.php
    │   └── NotificationCreationTest.php
    └── Unit/
        └── NotificationServiceTest.php

**Note:** Notification domain is a pure service layer with NO event listeners.
Event listeners are placed in the domains that own the business rules.
```

### Database Schema

#### `notifications` table

Stores notification content **once**, broadcast to multiple users.

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->integer('source_user_id')->nullable(); // User who triggered notification (null = system)
    $table->string('content_key'); // Translation key e.g., 'notification.comment.root'
    $table->json('content_data'); // Parameters for translation template
    $table->timestamps();
    
    $table->index('created_at');
});
```

#### `notification_reads` table

Tracks **which users should see each notification** and their read status.

**IMPORTANT**: One row per (notification_id, user_id) is created **at notification creation time** for all targeted users.

```php
Schema::create('notification_reads', function (Blueprint $table) {
    $table->foreignId('notification_id')->constrained()->onDelete('cascade');
    $table->integer('user_id'); // No foreign key to users (different domain)
    $table->timestamp('read_at')->nullable(); // NULL = unread, timestamp = read
    $table->timestamps();
    
    $table->primary(['notification_id', 'user_id']);
    $table->index(['user_id', 'read_at']); // Query: unread notifications for user
});
```

**Design Benefits:**
- Targeted notifications: N rows in notification_reads (one per recipient)
- Broadcast (News): 1 notification row + 1 row per user in notification_reads
- Query user notifications: `JOIN notification_reads WHERE user_id = ?`
- Unread count: `WHERE user_id = ? AND read_at IS NULL`
- Cleanup: Delete notifications older than 1 month (cascades notification_reads)

### Public API

#### `NotificationPublicApi`

Public service exposed to other domains. Located in `Notification/Public/Api/`.

**Key Methods:**
```php
// Create notification for specific users
public function createNotification(
    array $userIds,              // Target user IDs
    string $contentKey,          // Translation key e.g., 'story::notification.comment.root'
    array $contentData,          // Parameters for translation
    ?int $sourceUserId = null    // User who triggered (null = system)
): void

// Create broadcast notification for ALL users
public function createBroadcastNotification(
    string $contentKey,
    array $contentData,
    ?int $sourceUserId = null
): void

// Get unread count for a user (helper for tests or external usage)
public function getUnreadCount(int $userId): int

```

**Note**: `NotificationService` (Private/Services) handles database operations internally.
Public API primarily enables creation and read-count retrieval. Controllers and components use the `NotificationService` (Private) for fetching lists and actions.

### Architecture Pattern: Hybrid Approach

Notifications follow a **hybrid architecture** respecting the principle that domains should not listen to their own events:

#### Pattern A: Synchronous Notification Creation

When a domain **owns the notification business rule**, it calls NotificationPublicApi **synchronously** after the action:

**Example: News Domain**
```php
// In NewsService::publishNews()
public function publishNews(NewsData $data): News
{
    $news = $this->repository->create($data);
    
    event(new NewsPublished($news)); // For other purposes
    
    // Broadcast to all users
    $this->notificationPublicApi->createBroadcastNotification(
        contentKey: 'news::notification.posted',
        contentData: [
            'title' => $news->title,
            'slug' => $news->slug
        ],
        sourceUserId: $news->author_id
    );
    
    return $news;
}
```

**Example: Calendar Domain (JardiNo)**
```php
// In JardinoFlowerService::awardFlowers()
public function awardFlowers(User $user, int $count): void
{
    // Award logic...
    
    // Notify single user
    $this->notificationPublicApi->createNotification(
        userIds: [$user->id],
        contentKey: 'calendar::notification.jardino.flowers',
        contentData: ['count' => $count],
        sourceUserId: null // System notification
    );
}
```

#### Pattern B: Event Listener in Foreign Domain

When **another domain** owns the notification business rule, it listens to the event:

**Example: Story Domain Listening to CommentPosted**

Location: `Story/Private/Listeners/NotifyOnCommentListener.php`

```php
class NotifyOnCommentListener
{
    public function __construct(
        private NotificationPublicApi $notificationPublicApi
    ) {}
    
    public function handle(CommentPosted $event): void
    {
        if (!$event->comment->commentable instanceof Chapter) {
            return;
        }
        
        $chapter = $event->comment->commentable;
        $story = $chapter->story;
        $commenter = $event->comment->user;
        
        $contentData = [
            'commenter_name' => $commenter->name,
            'story_title' => $story->title,
            'story_slug' => $story->slug,
            'chapter_title' => $chapter->title,
            'chapter_slug' => $chapter->slug,
        ];
        
        if ($event->comment->isRoot()) {
            // Notify all story authors (exclude commenter)
            $authorIds = $story->authors
                ->pluck('id')
                ->filter(fn($id) => $id !== $commenter->id)
                ->toArray();
            
            $this->notificationPublicApi->createNotification(
                userIds: $authorIds,
                contentKey: 'story::notification.comment.root',
                contentData: $contentData,
                sourceUserId: $commenter->id
            );
        } else {
            // Notify parent comment author
            $parentAuthor = $event->comment->parent->user;
            if ($parentAuthor->id !== $commenter->id) {
                $this->notificationPublicApi->createNotification(
                    userIds: [$parentAuthor->id],
                    contentKey: 'story::notification.comment.reply',
                    contentData: $contentData,
                    sourceUserId: $commenter->id
                );
            }
        }
    }
}
```

**Key Point:** Story domain owns the rule "notify story authors on chapter comments" because it understands the relationship between chapters, stories, and authors.

### Notification Renderer

Location: `Notification/Public/Renderers/NotificationRenderer.php`

Responsible for rendering notification content from `content_key` and `content_data`.

```php
class NotificationRenderer
{
    public function render(Notification $notification): string
    {
        return __($notification->content_key, $notification->content_data);
    }
}
```

### Translation Templates

**Decentralized**: Each domain stores its own notification translations.

**Story Domain** - `Story/Resources/lang/fr/notification.php`:
```php
return [
    'comment.root' => ':commenter_name a commenté <a href="/story/:story_slug/:chapter_slug">:chapter_title</a> de votre histoire :story_title',
    'comment.reply' => ':commenter_name a répondu à votre commentaire sur <a href="/story/:story_slug/:chapter_slug">:chapter_title</a>',
];
```

**News Domain** - `News/Resources/lang/fr/notification.php`:
```php
return [
    'posted' => 'Nouvelle annonce : <a href="/news/:slug">:title</a>',
];
```

**Calendar Domain** - `Calendar/Resources/lang/fr/notification.php`:
```php
return [
    'jardino.flowers' => 'Vous avez gagné :count fleurs dans JardiNo ! 🌸',
];
```

### Routes

Registered in `Notification/Private/routes.php` and loaded by `NotificationServiceProvider`.

```php
// Web routes (authenticated only)
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/load-more', [NotificationController::class, 'loadMore'])->name('notifications.load-more');
});
```

### Controller

#### `NotificationController`

```php
public function index(): View // Full page
public function markAsRead(int $notificationId): JsonResponse // AJAX
public function markAllAsRead(): JsonResponse // AJAX
public function loadMore(Request $request): View // Returns Blade partial (like comment fragments)
```

### UI Components

#### Navigation Bell Icon

- Create a NotificationIconComponent within Notification domain, displayed with `@auth` guard in `navigation.blade.php`
- Display badge using the Private service (injected): unread count for `auth()->id()`
- Link to `/notifications`

#### Notifications Page

- Header: "Notifications" + "Mark all as read" button
- 10 notifications per page with "See more" button (loads partial via Alpine)
- Each item: avatar (or site icon), bold if unread, rendered content, checkmark button
- Alpine.js: AJAX mark as read, update badge count, toggle bold

```blade
@foreach($notifications as $notification)
    <div class="{{ $notification->read_at ? '' : 'font-bold' }}">
        @if($notification->source_user_id)
            <x-shared::avatar :user-id="$notification->source_user_id" />
        @else
            <x-shared::site-icon />
        @endif
        <div>{!! $notificationRenderer->render($notification) !!}</div>
        @if(!$notification->read_at)
            <button @click="markAsRead({{ $notification->id }})">✓</button>
        @endif
    </div>
@endforeach
```

### Jobs

#### `CleanupOldNotificationsJob`

Scheduled daily via Laravel scheduler.

**Logic:**
```php
// Delete all notifications older than 1 month
// Cascade will automatically delete associated read records
Notification::where('created_at', '<', now()->subDays(30))->delete();
```

### Authorization

- Use Policy: only the notification recipient can mark as read.
- Policy targets the `NotificationRead` row (join model) belonging to the current user.
- Controller resolves the row by `(notification_id, auth()->id())`; non-owners get 404/403.
- Mark-as-read is idempotent: sets `read_at = now()` when currently `NULL`.
- All routes require authentication.

### Translation Keys

**Notification domain** - UI labels only:
- `notification.title`, `notification.mark_all_read`, `notification.see_more`, `notification.no_notifications`

**Content templates**: See "Translation Templates" section (decentralized per domain).

### Testing Strategy

**Feature Tests:**
- Notification page auth only, mark as read, mark all as read, pagination, unread count
- NotificationPublicApi creates notification_reads rows for all target users
- NotificationRenderer renders with translation keys
- Cleanup job deletes old notifications
- Story listener notifies correct users on CommentPosted (root/reply)
- Commenter not notified of own comment
- Broadcast creates notification_reads for all users

Note: broadcast targets users with roles `user` and `user-confirmed`.

---

## Implementation Steps

### Phase 1: Core Infrastructure
1. Create Notification domain structure (no listeners)
2. Migrations: `notifications` and `notification_reads` tables
3. Models: Notification, NotificationRead (with NotificationFactory)
4. Services: NotificationPublicApi (Public), NotificationService (Private), NotificationRepository
5. Providers: NotificationServiceProvider (binds services, loads routes later)
6. NotificationRenderer

### Phase 2: UI
7. NotificationController + routes (loaded via domain provider)
8. Navigation bell icon + unread badge (uses Private service)
9. Notifications index page + notification-item component (ordered by `notifications.created_at` DESC)
10. AJAX mark as read (Alpine.js)
11. NotificationPolicy

### Phase 3: Integration
12. Story/Private/Listeners/NotifyOnCommentListener.php
13. Update NewsService to call NotificationPublicApi (emits NewsPublished)
14. Update JardinoFlowerService to call NotificationPublicApi
15. Add translation files to each domain (story::notification.*, etc.)
16. Register listeners in ServiceProviders

### Phase 4: Maintenance & Testing
16. CleanupOldNotificationsJob (scheduled daily)
17. Write tests

**Dependencies:** Create `NewsPosted` event if missing
**Dependencies:** `NewsPublished` event exists in News domain (see `app/Domains/News/Public/Events/NewsPublished.php`).

---

## Architectural Decisions

**1. Database Design**
- `notifications`: Store content once (1 row per notification)
- `notification_reads`: One row per (notification_id, user_id) created **at notification creation time**
- `read_at`: NULL = unread, timestamp = read
- Cleanup: Delete notifications older than 1 month (cascades to notification_reads)
- Default ordering for display: `notifications.created_at` DESC

**2. Domain Architecture** 
- Hybrid: Synchronous calls when domain owns rule (News, JardiNo), event listeners in foreign domains (Story → CommentPosted)
- Notification domain = pure service layer (no business logic, no listeners)

**3. Public API**
- `NotificationPublicApi` (Public/Services): Exposed to other domains
- `NotificationService` (Private/Services): Internal database operations
- Methods: `createNotification(userIds)`, `createBroadcastNotification()`, and `getUnreadCount(userId)`

**4. Content Storage**
- Store: `content_key` (translation key), `content_data` (JSON parameters)
- Translations: **Decentralized** per domain (story::notification.*, news::notification.*, etc.)
- No `NotificationType` enum needed (content_key identifies type)

**5. Icon Display**
- Store `source_user_id` (null = system)
- Resolve via `<x-shared::avatar />` component at display time

**6. Real-time Updates**
- Phase 1: Page refresh only
- Future: Websockets/polling in Phase 2

### Notes

- Naming: "JardiNo" is the translated label; use `jardino` in file/class identifiers and translation keys (e.g., `calendar::notification.jardino.flowers`).

