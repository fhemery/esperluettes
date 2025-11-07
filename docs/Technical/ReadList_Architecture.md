# ReadList Domain - Technical Architecture

## Overview

The ReadList domain enables users to bookmark stories for later reading. It follows DDD principles with event-driven communication.

**Domain Name**: `ReadList`  
**Story Prefix**: `RL-xxx`  
**Dependencies**: Story (Public API), Notification (Public API), Profile (Public API)

---

## Database Schema

### `read_list_entries` Table

```sql
read_list_entries
- id (bigint, PK, auto-increment)
- user_id (bigint, NOT NULL)
- story_id (bigint, NOT NULL)
- created_at (timestamp)
- updated_at (timestamp)

UNIQUE INDEX: (user_id, story_id)
INDEX: user_id
INDEX: story_id
```

**Deletion**: Hard delete only (no soft deletes)

---

## Domain Structure

```
app/Domains/ReadList/
├── Database/
│   └── Migrations/
│       └── YYYY_MM_DD_HHiiss_create_read_list_entries_table.php
├── Private/
│   ├── Controllers/
│   │   └── ReadListController.php
│   ├── Listeners/
│   │   ├── NotifyOnChapterPublished.php
│   │   ├── NotifyOnChapterUnpublished.php
│   │   ├── NotifyOnChapterDeleted.php
│   │   ├── NotifyOnStoryVisibilityChanged.php
│   │   ├── NotifyOnStoryDeleted.php
│   │   ├── RemoveDeletedStory.php
│   │   ├── NotifyAuthorsOnStoryAdded.php
│   │   └── RemoveUserEntries.php
│   ├── Models/
│   │   └── ReadListEntry.php
│   ├── Resources/
│   │   ├── lang/
│   │   │   └── fr/
│   │   │       ├── notifications.php
│   │   │       └── readlist.php
│   │   └── views/
│   │       ├── components/
│   │       │   ├── read-list-toggle.blade.php (for story page)
│   │       │   └── counter-badge.blade.php (for story page)
│   │       └── pages/
│   │           └── index.blade.php
│   ├── Services/
│   │   └── ReadListService.php
│   ├── View/
│   │   └── Components/
│   │       ├── ReadListToggleComponent.php
│   │       └── CounterBadgeComponent.php
│   └── Providers/
│       └── ReadListServiceProvider.php
├── Public/
│   ├── Api/
│   │   └── ReadListPublicApi.php
│   ├── Events/
│   │   ├── StoryAddedToReadList.php
│   │   └── StoryRemovedFromReadList.php
│   └── Notifications/
│       └── (NotificationContent classes if needed)
└── Tests/
    └── Feature/
```

---

## Story Public API Extension

Add new method to `StoryPublicApi`:

```php
/**
 * Fetch multiple stories with full data for Read List display.
 * Applies visibility rules and pagination.
 * 
 * @param int[] $storyIds Story IDs from user's read list
 * @param int $userId Current user ID (for visibility & progress)
 * @param array $options [
 *   'includeUpToDate' => bool (default: false),
 *   'genreId' => ?int (default: null),
 *   'page' => int (default: 1),
 *   'perPage' => int (default: 10),
 *   'sortBy' => string (default: 'updated_at'),
 * ]
 * @return array [
 *   'data' => ReadListStoryDto[],
 *   'pagination' => [...],
 * ]
 */
public function getStoriesForReadList(array $storyIds, int $userId, array $options = []): array
```

**Returns**: `ReadListStoryDto` with:
- Story data (id, title, slug, cover, summary, genres, authors, triggerWarnings, updatedAt)
- Chapters list (with reading progress marked)
- Progress calculation (readChapters / totalChapters)
- Visibility status

**Visibility handling**: Private stories are automatically excluded by `StoryPublicApi`.

---

## Reading Progress Integration

**Source**: Story domain's `ReadingProgress` model tracks `read_at` per chapter.

**Calculation**: ReadList computes progress from chapter data returned by `StoryPublicApi::getStoriesForReadList()`:
- Count total published chapters
- Count chapters marked as read (via ReadingProgress)
- Progress = (read / total) * 100

**First unread logic**: First chapter where `isRead === false`.

---

## Event Listeners

| Event | Listener | Action |
|-------|----------|--------|
| `Story.Deleted` | `RemoveDeletedStory` | Hard delete all `read_list_entries` for story + notify subscribers |
| `Story.VisibilityChanged` | `NotifyOnStoryVisibilityChanged` | Notify subscribers (different message for public↔private) |
| `Chapter.Published` | `NotifyOnChapterPublished` | Notify all users with story in read list |
| `Chapter.Unpublished` | `NotifyOnChapterUnpublished` | Notify subscribers |
| `Chapter.Deleted` | `NotifyOnChapterDeleted` | Notify subscribers |
| `Auth.UserDeleted` | `RemoveUserEntries` | Hard delete all read list entries for user |

**Internal event**: `StoryAddedToReadList` → triggers author notification via `NotifyAuthorsOnStoryAdded` listener.

All notifications use `NotificationPublicApi::createNotification()` directly.

---

## Notifications

### For Readers (users with story in read list)

| Trigger | Content Key |
|---------|-------------|
| New chapter published | `readlist.chapter_published` |
| Chapter unpublished | `readlist.chapter_unpublished` |
| Chapter deleted | `readlist.chapter_deleted` |
| Story becomes private | `readlist.story_private` |
| Story becomes public | `readlist.story_public` |
| Story deleted | `readlist.story_deleted` |

### For Authors

| Trigger | Content Key |
|---------|-------------|
| User adds story to read list | `readlist.story_added` |

**Implementation**: Create `NotificationContent` classes in `ReadList/Public/Notifications/` or use simple array payloads.

---

## Public API

`ReadListPublicApi` exposes:

```php
// Get count for story page badge
public function getReadListCountForStory(int $storyId): int

// Check if user has story in read list
public function hasStoryInReadList(int $userId, int $storyId): bool

// Add story to read list (returns bool success)
public function addToReadList(int $userId, int $storyId): bool

// Remove story from read list
public function removeFromReadList(int $userId, int $storyId): void

// Get user's read list story IDs (for initial query)
public function getReadListStoryIds(int $userId): array
```

---

## Story Domain Integration

### Counter Badge & Add/Remove Button

Story domain imports ReadList Blade components in story show page:

```blade
<x-read-list::counter-badge :story-id="$story->id" />
<x-read-list::add-button :story-id="$story->id" :story="$story" />
```

**Component location**: `app/Domains/ReadList/Private/Views/Components/`

**No caching**: Counter queries database on-demand.

**Dependency bypass**: Story imports ReadList components directly (acceptable for view layer).

---

## Filtering & Pagination

### Read List Page Filters

1. **"Up to Date" toggle** (default: ON): Hides stories where user has read all chapters
2. **Genre filter**: Single-select dropdown via `StoryRefLookupService`

**Filter application**: In `StoryPublicApi::getStoriesForReadList()` for proper pagination.

**Persistence**: Filters reset on page reload (no session storage).

---

## Frontend Implementation

### Lazy Loading (Infinite Scroll)

- **Mechanism**: Alpine.js Intersection Observer
- **Rendering**: Server-side HTML generation (Blade partial)
- **AJAX**: Fetch next page, append HTML fragment
- **Page size**: 10 stories per page

### Chapter List Display

**Reuse**: `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/reader-list.blade.php`

**New parameters**:
```php
@php
$showComments = $showComments ?? true;
$showViews = $showViews ?? true;
$truncated = $truncated ?? false; // ReadList sets to true
@endphp
```

**ReadList usage**: Pass `showComments: false, showViews: false, truncated: true`

### Truncated Chapter View

When `truncated === true`:
- Show: previous chapter (if exists) + first unread + next 3
- Show dots linking to story page
- Edge cases:
  - First unread is chapter 1: show 5 chapters
  - At end: show last 5 chapters
  - Up-to-date: show last 5 chapters

---

## Routes

```php
// In ReadList/Private/routes.php
Route::middleware(['auth'])->group(function () {
    Route::get('/read-list', [ReadListController::class, 'index'])
        ->name('readlist.index');
    
    Route::post('/read-list/{story}', [ReadListController::class, 'add'])
        ->name('readlist.add');
    
    Route::delete('/read-list/{story}', [ReadListController::class, 'remove'])
        ->name('readlist.remove');
    
    // Infinite scroll endpoint
    Route::get('/read-list/load-more', [ReadListController::class, 'loadMore'])
        ->name('readlist.load-more');
});
```

---

## Service Methods

`ReadListService` core methods:

```php
// Add story to read list + fire event
public function addStory(int $userId, int $storyId): bool

// Remove story from read list
public function removeStory(int $userId, int $storyId): void

// Get user's read list story IDs
public function getStoryIdsForUser(int $userId): array

// Get read list count for story
public function getCountForStory(int $storyId): int

// Check if story is in user's read list
public function hasStory(int $userId, int $storyId): bool

// Notify subscribers about story/chapter events
public function notifySubscribers(int $storyId, NotificationContent $content): void

// Get subscriber user IDs for a story
public function getSubscriberIds(int $storyId): array
```

---

## Dependencies Summary

**Outbound (ReadList uses)**:
- `StoryPublicApi::getStoriesForReadList()` - fetch stories with progress
- `NotificationPublicApi::createNotification()` - send notifications
- `ProfilePublicApi::getPublicProfiles()` - fetch author names (via Story API)
- `StoryRefLookupService` - genre data for filtering

**Inbound (Others use ReadList)**:
- Story domain: imports `counter-badge` and `add-button` components
- Story domain: can call `ReadListPublicApi::getReadListCountForStory()` if needed

---

## Testing Priorities

1. Adding/removing stories to/from read list
2. Event listeners trigger notifications correctly
3. Progress calculation accuracy
4. Filtering (up-to-date, genre)
5. Private story visibility handling
6. Infinite scroll pagination
7. User deletion cascades
8. Story deletion cascades
9. Author notifications on story added
10. Badge counter accuracy

---

## Open Technical Decisions

- [ ] Move `StoryRefLookupService` to Public API or keep as-is
- [ ] Define exact DTO structure for `ReadListStoryDto`
- [ ] Implement caching strategy later (if performance issues arise)
- [ ] Add Redis cache for counters if load increases

---

**Document Status**: Technical Design  
**Last Updated**: 2025-11-04
