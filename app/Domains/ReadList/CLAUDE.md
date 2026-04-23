# ReadList Domain — Agent Reference

- README: [app/Domains/ReadList/README.md](README.md)

---

## No Public API class

This domain has **no `ReadListPublicApi` class**. The `ReadListService` is private. Do not reach into it from other domains. Other domains interact with read-list state only through:
- The domain events (`StoryAddedToReadList`, `StoryRemovedFromReadList`) via the `EventBus`.
- `StoryPublicApi` (Story domain), which already supports read-list-aware queries via `StoryQueryFilterDto::onlyStoryIds`.

---

## Blade components for Story views

These two components are intended for use in Story domain views. They are class-based components resolved via the service container — pass them the correct props:

- `<x-read-list::read-list-toggle :story-id="$story->id" :is-author="$isAuthor" />` — the `isAuthor` flag must be supplied by the caller; the component does not look it up.
- `<x-read-list::read-list-counter :story-id="$story->id" />` — displays a count badge.

---

## Events emitted

Registered in `ReadListServiceProvider` via `EventBus::registerEvent()`.

- `StoryAddedToReadList` (`ReadList.Added`) — when a user successfully adds a story (idempotent: not emitted if already present)
- `StoryRemovedFromReadList` (`ReadList.Removed`) — when a user removes a story (only emitted if a row was actually deleted)

---

## Listens to

Subscribed in `ReadListServiceProvider::boot()` via `EventBus::subscribe()`.

| Event | Listener | Action |
|---|---|---|
| `Story::ChapterPublished` | `NotifyReadersOnChapterModified` | Notify readers who have access |
| `Story::ChapterCreated` (status=published) | `NotifyReadersOnChapterModified` | Notify readers who have access |
| `Story::ChapterUnpublished` | `NotifyReadersOnChapterModified` | Notify readers who have access |
| `Story::ChapterDeleted` | `NotifyReadersOnChapterModified` | Notify readers who have access (treated same as unpublished) |
| `Story::StoryDeleted` | `HandleStoryDeletedForReadList` | Notify all readers, then delete all entries for the story |
| `Story::StoryVisibilityChanged` | `HandleStoryVisibilityChangedForReadList` | Notify readers who gained or lost access |
| `Story::StoryUpdated` | `NotifyReadersOnStoryCompleted` | Notify readers if `isComplete` transitions from false to true |
| `Auth::UserDeleted` | `HandleUserDeletedForReadList` | Delete all read-list entries for the user |

---

## Non-obvious invariants

**Story-deleted notification bypasses access filter.** `HandleStoryDeletedForReadList` notifies all readers without calling `filterUsersWithAccessToStory()` — the story is already gone so access cannot be checked. This is intentional.

**`StoryRemovedFromReadList` is only emitted on actual deletion.** `ReadListService::removeStory()` checks the count of deleted rows; the event is not emitted if no row existed.

**Chapter notification access filter runs after fetching readers.** `NotifyReadersOnChapterModified` calls `ReadListService::getReadersForStory()` then passes the result to `StoryPublicApi::filterUsersWithAccessToStory()`. Both calls are needed; skipping the filter leaks chapter notifications to users who no longer have story access.

**`hide-up-to-date` filter request param wins over stored setting.** The controller checks `request()->has('hide_up_to_date')` first; only if the param is absent does it fall back to `SettingsPublicApi::getValue()`. When building filter-toggling UI, always pass an explicit `0` or `1` — never omit the param if the user changed the value.

**No FK constraint to `users`.** `read_list_entries.user_id` has no database foreign key. Cleanup on user deletion is handled entirely by the `Auth::UserDeleted` listener.

---

## Settings registration

The domain registers into the Settings domain from `ReadListServiceProvider`. Constants live on that class:

- `ReadListServiceProvider::TAB_READLIST` = `'readlist'`
- `ReadListServiceProvider::SECTION_GENERAL` = `'general'`
- `ReadListServiceProvider::KEY_HIDE_UP_TO_DATE` = `'hide-up-to-date'`

Use these constants (not string literals) when reading the setting value from `SettingsPublicApi`.
