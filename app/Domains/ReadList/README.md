# ReadList Domain

The ReadList domain lets authenticated users bookmark stories they want to read later ("Pile à Lire"). It owns the `read_list_entries` table and exposes Blade components for use inside Story views. Read lists are always private to the user; there is no sharing or public visibility.

Feature planning doc: [docs/Feature_Planning/ReadList.md](../../../docs/Feature_Planning/ReadList.md) — note the spec predates implementation and may be partially outdated.

---

## Key Concepts

### What a read list entry is

A `read_list_entries` row is simply a `(user_id, story_id)` pair. There is no ordering, priority, or status beyond the pair's existence. The display order on the list page is derived from the Story domain: stories are sorted by most recently updated (`stories.updated_at`) because the sort is applied when the Story API fetches the enriched DTOs using `onlyStoryIds`.

### No Public API class

This domain does not expose a dedicated `ReadListPublicApi` class. Other domains that need to know whether a story is in a user's read list, or to programmatically add/remove entries, must use the Story domain's `StoryPublicApi` — which already receives read-list-aware queries — or listen to the `StoryAddedToReadList` / `StoryRemovedFromReadList` events. The `ReadListService` is intentionally private.

### The add/remove toggle components

Two Blade components are designed to be embedded in Story domain views:

- `<x-read-list::read-list-toggle :story-id="..." :is-author="...">` — renders the add/remove button; renders nothing if the user is a guest, has no valid role, or is an author of the story.
- `<x-read-list::read-list-counter :story-id="...">` — renders the count of users who have bookmarked the story.

Both components call the `ReadListService` directly (they are class-based components resolved via the service container). The `isAuthor` flag must be passed in by the caller; the component does not look it up itself.

### Reading progress and "first unread chapter"

The list page displays reading progress (chapters read / total chapters) and a "Keep Reading" link to the first unread chapter. This data is not computed by the ReadList domain. It is requested from the Story domain via `StoryPublicApi::listStories()` with `includeReadingProgress: true` and `includeChapters: true`. The ReadList domain only assembles the result into its own view models.

### "Hide up-to-date" filter

Users can toggle a filter that hides stories where all published chapters have been read. This preference is stored via the Settings domain (`readlist` tab, `hide-up-to-date` key, default `false`). The controller reads the request query param first; if absent, it falls back to the stored setting. The filtering itself is delegated to the Story API via `StoryQueryReadStatus::UnreadOnly`.

### Infinite scroll / load-more

The index page loads the first 10 stories on a full page render. Subsequent pages are loaded via a `GET /readlist/load-more` endpoint that returns a JSON payload with an HTML fragment (rendered server-side from the `read-list-card` component) plus pagination metadata. The front-end uses this to append cards without a full page reload.

### Inline chapter list

Each story card on the index page has a toggle to expand a chapter list. Clicking it triggers a `GET /readlist/{storyId}/chapters` endpoint that returns a JSON payload containing a rendered HTML fragment (`read-list-chapter-list` component). Access is gated: only stories that are in the requesting user's read list are served; everything else gets a 404.

---

## Architecture Decisions

**No FK constraint to `users`**: The `read_list_entries` table stores `user_id` without a database-level foreign key, following the project-wide rule that inter-domain FK constraints are not allowed. User cleanup is handled reactively via the `Auth::UserDeleted` event.

**Story data is never duplicated**: The ReadList domain stores only IDs. All story metadata (title, cover, authors, chapters, progress) is fetched at read time via `StoryPublicApi`. This keeps the read list consistent without any synchronisation job.

**Authors cannot add their own stories**: The controller enforces this with a 403. The toggle component also hides itself when `isAuthor` is true. Both are independent guards; if one is bypassed the other still blocks.

**Notification access filtering**: Before sending chapter-published or chapter-unpublished notifications, the domain filters `getReadersForStory()` through `StoryPublicApi::filterUsersWithAccessToStory()`. This ensures users who lost access to a story (e.g. it became private after they bookmarked it) do not receive chapter notifications. The story-deleted notification bypasses this filter intentionally — the story is already gone so access cannot be checked, and all readers should be informed regardless.

---

## Cross-Domain Delegation

| What | Delegated to | Why |
|------|-------------|-----|
| Story metadata, read progress, chapter lists | `Story::StoryPublicApi` | Story domain owns the data and all access control logic |
| Reader profile display names | `Shared::ProfilePublicApi` | Profile data lives in the Profile domain |
| Notification delivery | `Notification::NotificationPublicApi` | Centralised notification system |
| "Hide up-to-date" preference storage | `Settings::SettingsPublicApi` | Extensible user-preference system |
| Genre options for filter dropdown | `StoryRef::StoryRefPublicApi` | Reference data for genres lives in StoryRef |
| User role checks (in toggle component) | `Auth::Roles` | Auth domain owns role constants |

---

## Notifications Emitted

All notifications are registered in the `NotificationFactory` via the service provider and delivered through `NotificationPublicApi`.

| Notification | Recipients | Trigger |
|---|---|---|
| `ReadListAddedNotification` | Story authors | User adds story to their read list (authors excluded if reader is a co-author) |
| `ReadListChapterPublishedNotification` | Readers with access | Chapter published or created-as-published |
| `ReadListChapterUnpublishedNotification` | Readers with access | Chapter unpublished or deleted |
| `ReadListStoryDeletedNotification` | All readers (no access check) | Story deleted |
| `ReadListStoryUnpublishedNotification` | Readers who lost access | Story visibility changed so they can no longer read it |
| `ReadListStoryRepublishedNotification` | Readers who gained access | Story visibility changed so they can now read it |
| `ReadListStoryCompletedNotification` | Readers with access | Story transitions from incomplete to complete (`isComplete` flag) |

---

## Settings Registration

The domain registers a dedicated tab in the Settings domain:

- Tab: `readlist` (order 20)
- Section: `general` (order 10)
- Parameter: `hide-up-to-date` (bool, default `false`)

Constants for these keys are on `ReadListServiceProvider` (`TAB_READLIST`, `SECTION_GENERAL`, `KEY_HIDE_UP_TO_DATE`).
