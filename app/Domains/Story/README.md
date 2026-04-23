# Story Domain

The core domain of the platform. It owns story and chapter CRUD, publication lifecycle, chapter credit management, reading progress tracking, collaborator management, and cover images. Reference data (genres, types, audiences, etc.) is intentionally split off into [StoryRef](../StoryRef/README.md) to keep this domain focused.

Explicitly out of scope: reading lists ([ReadList](../ReadList/README.md)), comments ([Comment](../Comment/README.md)), and time-bound activities around stories ([Calendar](../Calendar/README.md)).

---

## Key Concepts

### Visibility model

Stories have three visibility levels:

| Level | Who can read |
|-------|-------------|
| `public` | Everyone, including guests |
| `community` | Confirmed users only (`user-confirmed` role) |
| `private` | Authors and designated collaborators only |

The `StoryService` automatically strips `community` from query filters when the current user is not confirmed. Do not rely on callers to enforce this.

### Trigger warning disclosure modes

A story's `tw_disclosure` field controls *how* TW information is presented, not which TWs are attached:

- `listed` — TWs are shown upfront in the story header.
- `no_tw` — The author declares the story contains no trigger warnings. TW IDs must be empty.
- `unspoiled` — TWs exist but are hidden; the reader can reveal them on demand.

When `tw_disclosure` is not `listed`, the service layer clears `story_trigger_warnings` rows on save. The model does not enforce this — skipping it leaves orphaned TW rows that appear in API responses.

### Chapter credit system

Credits gate chapter creation to encourage community engagement:

- Each user starts with **5 credits** on registration (`UserRegistered` listener).
- Creating a chapter costs **1 credit** (checked before the DB transaction; spent after a successful save).
- Leaving a root comment on a published chapter authored by someone else earns **1 credit** (once per user/chapter pair, via `CommentPosted` listener).
- A moderation-deleted comment causes 1 credit to be revoked (`CommentDeletedByModeration` listener).

Credits are stored in `story_chapter_credits` as `credits_gained` and `credits_spent`, not as a single balance. Available balance = `gained − spent`; can be negative for users who pre-dated the system. The table has **no FK to `users`** — cleaned up explicitly on `UserDeleted`.

### Author vs. collaborator

Every user linked to a story is a `StoryCollaborator`. The `role` column distinguishes:

| Role | Access |
|------|--------|
| `author` | Full write access; the story creator is automatically added as author on creation |
| `beta-reader` | Read access to private chapters; no write permissions |

"Author" throughout the codebase means a collaborator with `role = author`.

### Cover types

Stories support three cover modes, controlled by `cover_type`:

| Type | Description |
|------|-------------|
| `default` | Platform SVG adapting to seasonal themes |
| `themed` | Pre-defined image derived from the story's genre selection; genre slug stored in `cover_data` |
| `custom` | User-uploaded image (feature-flagged; off by default via `story.custom_covers_enabled`) |

Use `CoverService` to resolve cover URLs. On a themed genre removal during story update, the cover reverts to `default` automatically. Both `themed` and `custom` supports an HD version for lightbox display.

---

## Architecture Decisions

**Slugs include the record ID.** Both story and chapter slugs are stored as `{slug-base}-{id}` using `SlugWithId::build()`. This guarantees global uniqueness without a uniqueness check and makes slug parsing via `SlugWithId::extractId()` reliable. A temporary random slug is inserted first (since the ID is not yet known at insert time), then replaced immediately after the first `save()`.

**Sparse chapter sort ordering.** Chapters are ordered by `sort_order` in increments of 100 (100, 200, 300…). New chapters append at `max(sort_order) + 100`. Explicit reorders use `SparseReorder::computeChanges()` which re-spaces all chapters. Never insert at a fixed offset.

**Soft delete on deactivation, hard delete on account deletion.** When a user is deactivated, their stories are soft-deleted (recoverable if the account is reactivated). When the account is permanently deleted, stories are hard-deleted. These are handled by two separate listeners — `SoftDeleteStoriesOnUserDeactivated` and `RemoveStoriesOnUserDeleted` — and must not be merged.

**No foreign key from `story_chapter_credits` to `users`.** Per architecture rules, no cross-domain FK to the `users` table is allowed. The credit row is deleted explicitly by a `UserDeleted` listener.

**Admin Filament resources** for story moderation live in `app/Domains/Admin/`, not inside this domain. The Story domain registers its admin navigation entry (the moderation index page) via `AdminNavigationRegistry`.

**Chapter observer.** `ChapterObserver` computes `word_count` and `character_count` automatically on `saving` — do not set these fields manually.

---

## Public API

Other domains interact with Story through `StoryPublicApi` (`Public/Api/StoryPublicApi.php`):

| Method | Description |
|--------|-------------|
| `listStories(filter, pagination, fields)` | Paginated story list with rich filter and optional field inclusion |
| `getStoriesForUser(userId, excludeCoauthored)` | Lightweight list of authored stories as `UserStoryListItemDto[]` |
| `getStory(storyId)` | Single story fetch by ID, returns `StorySummaryDto` or `null` |
| `isAuthor(userId, storyId)` | Check if a user is an author of a given story |
| `getAuthorIds(storyId)` | Return all author user IDs for a story |
| `countAuthoredStories(userId)` | Count stories where user is an author |
| `searchStories(query, viewerUserId, limit)` | Full-text search returning `StorySearchResultDto[]` |
| `filterUsersWithAccessToStory(userIds, storyId)` | Filter provided user IDs to those with access (respects visibility rules) |
| `diffAccessForUsers(userIds, storyId, previousVisibility)` | Compute users who gained/lost access after a visibility change |

### Filter DTO (`StoryQueryFilterDto`)

Supports: `visibilities`, `genreIds`, `typeIds`, `audienceIds`, `triggerWarningIds` (exclusion), `onlyStoryIds`, `authorIds`, `readStatus` (All/Read/Unread), `noTwOnly`, `withPublishedChapterOnly`.

### Fields DTO (`StoryQueryFieldsToReturnDto`)

Opt-in expensive fields: `includeAuthors`, `includeCollaborators`, `includeGenreIds`, `includeTriggerWarningIds`, `includeChapters`, `includeReadingProgress`, `includeWordCount`, `includePublishedChaptersCount`.

---

## Domain Events

### Emitted

| Event | Payload summary | When |
|-------|----------------|------|
| `StoryCreated` | `StorySnapshot` | Story created |
| `StoryUpdated` | before/after `StorySnapshot` | Story metadata updated |
| `StoryDeleted` | before `StorySnapshot` + `ChapterSnapshot[]` | Story hard-deleted |
| `StoryVisibilityChanged` | storyId, title, old/new visibility | Visibility field changed on create or update |
| `StoryExcludedFromEvents` | storyId, title | `is_excluded_from_events` toggled to `true` |
| `StoryModeratedAsPrivate` | storyId, title | Moderator forced story to private |
| `StorySummaryModerated` | storyId, title | Moderator cleared story description |
| `StoryCoverModerated` | storyId, title, storyOwnerId | Moderator reset story cover to default |
| `ChapterCreated` | storyId, `ChapterSnapshot` | Chapter created |
| `ChapterUpdated` | storyId, before/after `ChapterSnapshot` | Chapter content or metadata updated |
| `ChapterPublished` | storyId, `ChapterSnapshot` | Chapter transitions to published |
| `ChapterUnpublished` | storyId, `ChapterSnapshot` | Chapter unpublished by author |
| `ChapterUnpublishedByModeration` | storyId, chapterId, title | Chapter unpublished by a moderator |
| `ChapterDeleted` | storyId, `ChapterSnapshot` | Chapter deleted |
| `ChapterContentModerated` | storyId, chapterId, title | Moderator emptied chapter content |
| `ChapterCommentNotificationsBackfilled` | — | Admin backfill command ran |
| `ModeratorAccessedPrivateStory` | storyId | Moderator viewed a private story |
| `ModeratorAccessedPrivateChapter` | chapterId | Moderator viewed a private chapter |

### Consumed

| Source event | Listener | Action |
|-------------|---------|--------|
| `Auth::UserRegistered` | `GrantInitialCreditsOnUserRegistered` | Grant 5 initial chapter credits |
| `Auth::UserDeactivated` | `SoftDeleteStoriesOnUserDeactivated` | Soft-delete all stories by that author |
| `Auth::UserReactivated` | `RestoreStoriesOnUserReactivated` | Restore soft-deleted stories and chapters |
| `Auth::UserDeleted` | `RemoveStoriesOnUserDeleted` | Hard-delete all stories (+ chapter comments via `CommentMaintenancePublicApi`) |
| `Auth::UserDeleted` | `RemoveChapterCreditsOnUserDeleted` | Delete the `story_chapter_credits` row |
| `Comment::CommentPosted` | `GrantCreditOnRootCommentPosted` | Grant 1 credit (root comment, published chapter, non-author, first time per user/chapter) |
| `Comment::CommentPosted` | `MarkChapterReadOnRootCommentPosted` | Mark chapter as read for the commenter |
| `Comment::CommentPosted` | `NotifyOnChapterComment` | Notify story authors via `ChapterCommentNotification` |
| `Comment::CommentDeletedByModeration` | `DecreaseCreditsOnCommentDeletedListener` | Revoke 1 credit |

---

## Database Schema

| Table | Description |
|-------|-------------|
| `stories` | Core story record: title, slug, description, visibility, ref IDs, cover, flags |
| `story_chapters` | Chapter content: title, slug, content, sort_order, status, word/char counts |
| `story_chapter_credits` | Credit ledger per user: `credits_gained`, `credits_spent` — no FK to `users` |
| `story_collaborators` | User-to-story membership: `role` (author/beta-reader), invite metadata |
| `story_genres` | Pivot: story → `story_ref_genres` (1..3 required) |
| `story_reading_progress` | Logged reads: user_id, story_id, chapter_id, read_at |
| `story_trigger_warnings` | Pivot: story → `story_ref_trigger_warnings` (0..N, cleared when disclosure ≠ `listed`) |

Key constraints:
- `stories.slug` — unique index
- `story_chapters.slug` — unique index; stored with `-{id}` suffix
- `story_chapters.story_id` — FK to `stories` with cascade delete
- `story_chapters` compound index on `(story_id, sort_order)`
- `story_chapter_credits.user_id` — no FK (cross-domain prohibition)

---

## HTTP Routes

All routes are under the `web` middleware. Slug parameters use `.*` patterns to allow slashes.

| Method | Path | Controller | Auth requirement |
|--------|------|-----------|-----------------|
| `GET` | `/stories` | `StoryController@index` | Public |
| `GET` | `/stories/create` | `StoryCreateController@create` | `user-confirmed` |
| `POST` | `/stories` | `StoryController@store` | `user-confirmed` |
| `GET` | `/stories/{slug}` | `StoryController@show` | Public (visibility-gated) |
| `GET` | `/stories/{slug}/edit` | `StoryController@edit` | `user-confirmed` + author |
| `PUT/PATCH` | `/stories/{slug}` | `StoryController@update` | `user-confirmed` + author |
| `DELETE` | `/stories/{slug}` | `StoryController@destroy` | `user-confirmed` + author |
| `GET` | `/stories/{storySlug}/chapters/create` | `ChapterController@create` | `user-confirmed` + author |
| `POST` | `/stories/{storySlug}/chapters` | `ChapterController@store` | `user-confirmed` + author |
| `GET` | `/stories/{storySlug}/chapters/{chapterSlug}` | `ChapterController@show` | Public (visibility-gated) |
| `GET` | `/stories/{storySlug}/chapters/{chapterSlug}/edit` | `ChapterController@edit` | `user-confirmed` + author |
| `PUT/PATCH` | `/stories/{storySlug}/chapters/{chapterSlug}` | `ChapterController@update` | `user-confirmed` + author |
| `DELETE` | `/stories/{storySlug}/chapters/{chapterSlug}` | `ChapterController@destroy` | `user-confirmed` + author |
| `PUT` | `/stories/{storySlug}/chapters/reorder` | `ChapterController@reorder` | `user-confirmed` + author |
| `GET` | `/stories/{slug}/collaborators` | `CollaboratorController@index` | `user-confirmed` + author |
| `POST` | `/stories/{slug}/collaborators` | `CollaboratorController@store` | `user-confirmed` + author |
| `DELETE` | `/stories/{slug}/collaborators/{targetUserId}` | `CollaboratorController@destroy` | `user-confirmed` + author |
| `POST` | `/stories/{slug}/collaborators/leave` | `CollaboratorController@leave` | authenticated |
| `POST` | `/stories/{storySlug}/chapters/{chapterSlug}/read` | `ReadingProgressController@markRead` | authenticated |
| `DELETE` | `/stories/{storySlug}/chapters/{chapterSlug}/read` | `ReadingProgressController@unmarkRead` | authenticated |
| `GET` | `/stories/{storyId}/profile-comments/{userId}` | `ProfileCommentsApiController@getCommentsForStory` | `user-confirmed` |
| `GET` | `/stories/admin/moderation` | `StoryModerationAdminController@index` | moderator/admin |
| `GET` | `/stories/admin/moderation/{story}/chapters` | `StoryModerationAdminController@chapters` | moderator/admin |
| `POST` | `/stories/{slug}/moderation/make-private` | `StoryModerationController@makePrivate` | moderator/admin |
| `POST` | `/stories/{slug}/moderation/empty-summary` | `StoryModerationController@emptySummary` | moderator/admin |
| `POST` | `/stories/{slug}/moderation/remove-cover` | `StoryModerationController@removeCover` | moderator/admin |
| `POST` | `/chapters/{slug}/moderation/unpublish` | `ChapterModerationController@unpublish` | moderator/admin |
| `POST` | `/chapters/{slug}/moderation/empty-content` | `ChapterModerationController@emptyContent` | moderator/admin |

---

## Registry Integrations

| Registry | Domain | What is registered |
|---------|--------|-------------------|
| `CommentPolicyRegistry` | Comment | `'chapter'` topic with `ChapterCommentPolicy` |
| `ModerationRegistry` | Moderation | `'story'` and `'chapter'` topics with `StorySnapshotFormatter` / `ChapterSnapshotFormatter` |
| `NotificationFactory` | Notification | 7 notification types: `ChapterCommentNotification`, `CoAuthorChapterCreated/Updated/DeletedNotification`, `CollaboratorRoleGiven/Removed/LeftNotification` |
| `ConfigPublicApi` | Config | Feature toggles `story.theme_covers_enabled` and `story.custom_covers_enabled` (both default `false`) |
| `AdminNavigationRegistry` | Administration | Story moderation index page under admin navigation (moderator/admin roles, priority 10) |

---

## Cross-Domain Delegation

| Concern | Delegated to | Via |
|---------|-------------|-----|
| Reference data (genres, types, audiences, etc.) | StoryRef | `StoryRefPublicApi` |
| Reading lists | ReadList | — (separate domain) |
| Comments | Comment | `CommentPublicApi`, `CommentMaintenancePublicApi` |
| Calendar activities | Calendar | — (separate domain) |
| Author display names | Profile | `ProfilePublicApi` |
| Notification delivery | Notification | `NotificationPublicApi` |
| User authentication/roles | Auth | `AuthPublicApi` |

---

## Feature Flags

| Toggle key | Default | Controls |
|------------|---------|---------|
| `story.theme_covers_enabled` | `false` | Show themed cover tab in story create/edit form |
| `story.custom_covers_enabled` | `false` | Show custom cover upload tab in story create/edit form |
