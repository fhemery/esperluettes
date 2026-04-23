# Comment Domain

This domain provides a pluggable comment system with per-entity policy registry. It is designed to be consumed by other domains (Story, News, etc.) rather than to serve a standalone UI.

## Architecture overview

Comments are stored in a single `comments` table using a polymorphic-style `commentable_type` / `commentable_id` pair. This allows any entity in any domain to receive comments without requiring a schema change in this domain.

Replies are one level deep only: a reply's `parent_comment_id` must point to a root comment. Nesting beyond one level is rejected by the API.

The domain is built around three extension points:

1. **CommentPolicyRegistry** — per-entity-type rules (who can post, length limits, edit permissions)
2. **ModerationRegistry** — registers the `comment` topic so moderators can act on reports
3. **EventBus** — emits domain events on every write operation

See the retrieval sequence diagram:

![Comment retrieval flow](./Docs/Diagrams/Comment%20Retrieval%20Sequence.png)

## Public API

### CommentPublicApi

The main entry point for reading and writing comments. Requires an authenticated user for all write operations and for reading.

| Method | Description |
|--------|-------------|
| `getFor(entityType, entityId, page, perPage)` | Returns a paginated `CommentListDto` with root comments and their direct children. When `page <= 0`, returns metadata and totals only (lazy mode for the Blade component). |
| `create(CommentToCreateDto)` | Creates a root comment or a reply. Enforces policy rules (canCreateRoot, length limits, validateCreate). Returns the new comment ID. |
| `edit(commentId, newBody)` | Edits the caller's own comment. Enforces ownership and policy rules. Returns an updated `CommentDto`. |
| `getComment(commentId, withChildren)` | Fetches a single comment DTO with optional eager-loaded children. Requires auth. |
| `getCommentInternal(commentId, withChildren, contextUserId)` | Same as `getComment` but without auth gate; for internal/admin use. |
| `userHasRoot(entityType, entityId, userId)` | Returns whether the user already has a root comment on the entity. |
| `getNbRootComments(entityType, entityId, authorId?)` | Count of root comments for a single entity, optionally filtered by author. |
| `getNbRootCommentsFor(entityType, entityIds[])` | Bulk count of root comments, keyed by entity ID. |
| `countRootCommentsByUser(entityType, userId)` | Total root comments posted by a user for an entity type. |
| `hasUnrepliedRootComments(entityType, entityIds[], authorIds[])` | Bulk check: for each entity, does at least one root comment from the given authors have no reply? |
| `getEntityIdsWithRootCommentsByAuthor(entityType, authorId)` | Entity IDs where an author has at least one root comment. |
| `getRootCommentsByAuthorAndEntities(entityType, authorId, entityIds[])` | Root comments by a given author for specific entities, keyed by entity ID. |

### CommentMaintenancePublicApi

System-level operations. Intended for use by other domains cleaning up their own data.

| Method | Description |
|--------|-------------|
| `deleteFor(entityType, entityId)` | Soft-deletes all comments (roots and replies) for a given target. Returns affected row count. |

### CommentPolicyRegistry

A singleton registry that maps entity type strings to `CommentPolicy` implementations. When no policy is registered for an entity type, the `DefaultCommentPolicy` (allow all, no length limits) applies.

| Method | Purpose |
|--------|---------|
| `register(entityType, CommentPolicy)` | Register a policy for an entity type |
| `canCreateRoot(entityType, entityId, userId)` | Can the user post a root comment? |
| `canReply(entityType, parentComment, userId)` | Can the user reply to this comment? |
| `canEditOwn(entityType, comment, userId)` | Should the edit control be shown? |
| `validateCreate(CommentToCreateDto)` | Additional domain-specific create validation (throw to block) |
| `validateEdit(entityType, comment, userId, newBody)` | Additional domain-specific edit validation (throw to block) |
| `getRootCommentMinLength(entityType)` | Min plain-text length for root comments (null = no limit) |
| `getRootCommentMaxLength(entityType)` | Max plain-text length for root comments (null = no limit) |
| `getReplyCommentMinLength(entityType)` | Min plain-text length for replies (null = no limit) |
| `getReplyCommentMaxLength(entityType)` | Max plain-text length for replies (null = no limit) |
| `getUrl(entityType, entityId, commentId)` | Contextual URL to view the comment (used by Moderation) |

## Registering a policy

Implement `CommentPolicy` (or extend `DefaultCommentPolicy` to override only what you need), then register in your domain's service provider:

```php
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;

public function boot(): void
{
    $registry = app(CommentPolicyRegistry::class);
    $registry->register('chapter', app(ChapterCommentPolicy::class));
}
```

Example implementation: `App\Domains\Story\Private\Services\ChapterCommentPolicy`, registered in `StoryServiceProvider`.

## DTOs

| Class | Description |
|-------|-------------|
| `CommentToCreateDto` | Input for `create()` — entity type, entity ID, body, optional parent comment ID |
| `CommentDto` | A single comment with author profile, permission flags (`canReply`, `canEditOwn`), and nested children |
| `CommentListDto` | Paginated list of `CommentDto` items plus a `CommentUiConfigDto` |
| `CommentUiConfigDto` | UI configuration: length limits and `canCreateRoot` flag |

## Events emitted

All events implement `DomainEvent` and are registered with the `EventBus` in `CommentServiceProvider`.

| Event class | Event name | When |
|-------------|------------|------|
| `CommentPosted` | `Comment.Posted` | A comment or reply is created |
| `CommentEdited` | `Comment.Edited` | A comment body is edited by its author |
| `CommentDeletedByModeration` | `Comment.DeletedByModeration` | A moderator hard-deletes a comment |
| `CommentContentModerated` | `Comment.ContentModerated` | A moderator replaces a comment's body with the default text |

`CommentPosted` and `CommentEdited` carry a `CommentSnapshot` DTO (word count, char count, entity context, author ID, reply/root flag).

## Listens to

| Event | Action |
|-------|--------|
| `Auth::UserDeleted` | Nullifies `author_id` on all comments by that user (content is preserved) |
| `Auth::UserDeactivated` | Soft-deletes all comments by that user |
| `Auth::UserReactivated` | Restores soft-deleted comments by that user |

## Blade component

`<x-comment::comment-list :entityType="..." :entityId="..." :perPage="5" :page="1" />`

The `CommentListComponent` supports two loading modes:

- **Eager** (`page >= 1`): loads the requested page immediately on server render.
- **Lazy** (`page <= 0`): returns metadata and total count only; client triggers fragment loading via an Intersection Observer.

It also supports **deep linking**: when the request contains a `?comment={id}` query parameter, the component pre-loads pages until the target comment is found, then passes a `targetCommentId` to the Blade template for client-side scroll-and-highlight.

## Routes

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/comments` | `auth`, `compliant` | Create a comment or reply |
| `PATCH` | `/comments/{commentId}` | `auth`, `compliant` | Edit own comment |
| `POST` | `/comments/{commentId}/empty-content` | Moderator+ | Replace body with default text |
| `DELETE` | `/comments/{commentId}` | Moderator+ | Hard-delete comment and its replies |
| `GET` | `/comments/fragments` | public | Return HTML fragment for lazy-load pagination |

## Database

### `comments` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `commentable_type` | string(64) | Entity type string (e.g. `'chapter'`) |
| `commentable_id` | unsignedBigInteger | Entity ID |
| `author_id` | unsignedBigInteger, nullable | No FK to `users` (cross-domain FK prohibited); nullified on user deletion |
| `parent_comment_id` | unsignedBigInteger, nullable | Null for root; points to a root comment for replies |
| `is_active` | boolean | Moderation flag |
| `body` | text | HTML, sanitized via HTMLPurifier `strict` profile |
| `edited_at` | timestamp, nullable | Set when body is edited |
| `deleted_at` | timestamp, nullable | Soft deletes |

Composite index on `(commentable_type, commentable_id, created_at)` for efficient listing.

## Body sanitization

All bodies pass through `CommentBodySanitizer`, which runs HTMLPurifier with the `strict` profile before persistence. Length checks operate on the plain-text length (after stripping tags) of the sanitized output.

## Moderation integration

The domain registers a `comment` topic with the `ModerationRegistry`. When a comment is reported, moderators see a formatted snapshot rendered by `CommentSnapshotFormatter` and can act via the moderation admin panel (empty content or delete).
