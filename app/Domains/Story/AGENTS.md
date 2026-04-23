# Story Domain — Agent Instructions

- README: [app/Domains/Story/README.md](README.md)

## Public API

`StoryPublicApi` (`Public/Api/StoryPublicApi.php`) is the only entry point for other domains.

| Method | Purpose |
|--------|---------|
| `listStories(filter, pagination, fields)` | Paginated story list; filter via `StoryQueryFilterDto`, opt-in fields via `StoryQueryFieldsToReturnDto` |
| `getStoriesForUser(userId, excludeCoauthored)` | Lightweight `UserStoryListItemDto[]` for a user's authored stories |
| `getStory(storyId)` | Single story by ID as `StorySummaryDto` or `null` |
| `isAuthor(userId, storyId)` | Boolean author check |
| `getAuthorIds(storyId)` | `int[]` of author user IDs |
| `countAuthoredStories(userId)` | Count of stories where user is an author |
| `searchStories(query, viewerUserId, limit)` | Full-text title search returning `StorySearchResultDto[]` (max 25) |
| `filterUsersWithAccessToStory(userIds, storyId)` | Filter to users who can access the story under current visibility |
| `diffAccessForUsers(userIds, storyId, previousVisibility)` | Returns `['gained' => int[], 'lost' => int[]]` after a visibility change |

## Events emitted

| Event | When |
|-------|------|
| `StoryCreated` | Story created |
| `StoryUpdated` | Story metadata updated |
| `StoryDeleted` | Story hard-deleted |
| `StoryVisibilityChanged` | Visibility field changed (on create or update) |
| `StoryExcludedFromEvents` | `is_excluded_from_events` toggled to `true` |
| `StoryModeratedAsPrivate` | Moderator forced story to private visibility |
| `StorySummaryModerated` | Moderator cleared story description |
| `StoryCoverModerated` | Moderator reset story cover |
| `ChapterCreated` | Chapter created |
| `ChapterUpdated` | Chapter content or metadata updated |
| `ChapterPublished` | Chapter transitions to published |
| `ChapterUnpublished` | Chapter unpublished by author |
| `ChapterUnpublishedByModeration` | Chapter unpublished by a moderator |
| `ChapterDeleted` | Chapter deleted |
| `ChapterContentModerated` | Moderator emptied chapter content |
| `ChapterCommentNotificationsBackfilled` | Admin backfill command ran |
| `ModeratorAccessedPrivateStory` | Moderator viewed a private story |
| `ModeratorAccessedPrivateChapter` | Moderator viewed a private chapter |

## Listens to

| Event | Listener | Action |
|-------|---------|--------|
| `Auth::UserRegistered` | `GrantInitialCreditsOnUserRegistered` | Grant 5 initial chapter credits |
| `Auth::UserDeactivated` | `SoftDeleteStoriesOnUserDeactivated` | Soft-delete all stories by that author |
| `Auth::UserReactivated` | `RestoreStoriesOnUserReactivated` | Restore soft-deleted stories and chapters |
| `Auth::UserDeleted` | `RemoveStoriesOnUserDeleted` | Hard-delete all stories |
| `Auth::UserDeleted` | `RemoveChapterCreditsOnUserDeleted` | Delete the `story_chapter_credits` row |
| `Comment::CommentPosted` | `GrantCreditOnRootCommentPosted` | Grant 1 credit (root comment, published chapter, non-author, first per user/chapter) |
| `Comment::CommentPosted` | `MarkChapterReadOnRootCommentPosted` | Mark chapter as read for the commenter |
| `Comment::CommentPosted` | `NotifyOnChapterComment` | Notify story authors via `ChapterCommentNotification` |
| `Comment::CommentDeletedByModeration` | `DecreaseCreditsOnCommentDeletedListener` | Revoke 1 credit |

## Non-obvious invariants

**Slug format.** Both story and chapter slugs are stored as `{base}-{id}`. Use `SlugWithId::build($base, $id)` to generate and `SlugWithId::extractId($slug)` to parse. Never hand-craft slugs — this breaks URL resolution. On creation, a temporary random slug is inserted first, then replaced after the record has an ID.

**TW IDs when disclosure is not `listed`.** When `tw_disclosure` is `no_tw` or `unspoiled`, the `story_trigger_warnings` rows must be cleared on save. The model does not enforce this — the service layer does. Skipping this leaves orphaned TW rows that appear in API responses.

**Chapter credits: check before, spend after.** The check `availableForUser <= 0` throws an `AuthorizationException` before the DB transaction starts. Credits are spent via `spendOne()` *after* a successful save. Do not reorder this sequence.

**Soft delete vs. hard delete.** `SoftDeleteStoriesOnUserDeactivated` calls `softDeleteStoriesByAuthor`; `RemoveStoriesOnUserDeleted` hard-deletes. These are separate listeners for separate events — do not merge them.

**`story_chapter_credits` has no FK to `users`.** Cross-domain FK to `users` is prohibited by architecture. The credits row is cleaned up explicitly by the `RemoveChapterCreditsOnUserDeleted` listener on `UserDeleted`.

**Chapter sort order increments by 100.** New chapters append at `max(sort_order) + 100`. The `SparseReorder` helper re-spaces all chapters when an explicit reorder is requested. Never insert at a fixed offset.

**`ChapterObserver` computes word and character counts.** `word_count` and `character_count` on `story_chapters` are set by the observer on `saving`. Do not set these fields manually in service code.

**Themed cover falls back to `default` on genre removal.** In `StoryService::updateStory`, if the themed cover's genre slug is no longer among the selected genres, `cover_type` is reset to `default` and `cover_data` is cleared before saving.

**`StoryService::searchStories` enforces role-based visibility.** The service always removes `community` from the visibility filter when the current user does not have the `user-confirmed` role, regardless of what the caller passed in. Do not bypass this by calling the repository directly.

**Chapter deletion purges comments.** `ChapterService::deleteChapter` calls `CommentMaintenancePublicApi::deleteFor('chapter', $id)` before `forceDelete()`. Story deletion in `StoryService::deleteStory` also calls this for each chapter. This is the only way to clean cross-domain comment data.

## Registry integrations

- **CommentPolicyRegistry** (`Comment` domain) — registers `'chapter'` policy with `ChapterCommentPolicy`.
- **ModerationRegistry** (`Moderation` domain) — registers `'story'` and `'chapter'` topics with `StorySnapshotFormatter` and `ChapterSnapshotFormatter`.
- **NotificationFactory** (`Notification` domain) — registers 7 notification types: `ChapterCommentNotification`, `CoAuthorChapterCreated/Updated/DeletedNotification`, `CollaboratorRoleGiven/Removed/LeftNotification`.
- **ConfigPublicApi** (`Config` domain) — registers feature toggles `story.theme_covers_enabled` and `story.custom_covers_enabled` (both default `false`).
- **AdminNavigationRegistry** (`Administration` domain) — registers the story moderation index page under admin navigation (priority 10, roles: moderator/admin/tech-admin).
