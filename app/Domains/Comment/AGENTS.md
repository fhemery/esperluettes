# Comment Domain — Agent Instructions

- README: [app/Domains/Comment/README.md](README.md)

## Public API

- [CommentPublicApi](Public/Api/CommentPublicApi.php) — CRUD and read operations; main entry point for all comment interactions. Requires authenticated user for write and read. Delegates body sanitization to `CommentBodySanitizer` and permission decisions to `CommentPolicyRegistry`.
- [CommentMaintenancePublicApi](Public/Api/CommentMaintenancePublicApi.php) — system-level bulk delete for use by owning domains (e.g. when a chapter is deleted).
- [CommentPolicyRegistry](Public/Api/CommentPolicyRegistry.php) — singleton; maps entity type strings to `CommentPolicy` implementations. Falls back to `DefaultCommentPolicy` (allow all, no length limits) when no policy is registered.

## Events emitted

| Event | When |
|-------|------|
| `Comment.Posted` | Comment or reply created |
| `Comment.Edited` | Comment body edited by its author |
| `Comment.DeletedByModeration` | Moderator hard-deletes a comment and its replies |
| `Comment.ContentModerated` | Moderator replaces comment body with default text |

## Listens to

| Event | Action |
|-------|--------|
| `Auth::UserDeleted` | Nullifies `author_id` on all comments (content preserved) |
| `Auth::UserDeactivated` | Soft-deletes all comments by that user |
| `Auth::UserReactivated` | Restores soft-deleted comments by that user |

## Non-obvious invariants

**Replies are one level deep only.** `parent_comment_id` must point to a root comment (one with `parent_comment_id = null`). The API rejects attempts to reply to an existing reply with a validation error. Do not relax this constraint without updating the UI loading logic.

**No FK to `users`.** `author_id` is a plain `unsignedBigInteger` with no foreign key constraint. On `UserDeleted`, the listener nullifies `author_id` rather than deleting comments. On `UserDeactivated`, comments are soft-deleted and restored on `UserReactivated`.

**Body sanitization happens before length checks.** `CommentBodySanitizer` strips disallowed HTML via HTMLPurifier (`strict` profile), then `plainTextLength()` strips tags to compute plain-text character count. Policy min/max limits are applied to this plain-text length, not the raw submitted body.

**`page <= 0` triggers lazy mode in `CommentPublicApi::getFor()`.** In this mode the method returns metadata and total count only, with an empty `items` array. The Blade component `CommentListComponent` uses this to defer item loading to the Intersection Observer fragment endpoint (`GET /comments/fragments`).

**Deep-link pre-loading is unbounded.** When `?comment={id}` is present in the request, `CommentListComponent` loads pages in a loop until the target comment is found. If the comment does not exist on the entity, the loop terminates when items run out; it does not throw.

**`canCreateRoot()` is enforced, `canReply()` is not.** `CommentPublicApi::create()` calls `canCreateRoot()` on the root path, but the reply path only validates the parent (same target, and it is a root) plus the reply length limits. `canReply()` is used solely to set the `canReply` flag on `CommentDto`, i.e. to show or hide the UI control. A policy that must actually forbid replying cannot express it through `canReply()` today — it has to throw from `validateCreate()`, which is the only hook called on both paths (`NewsCommentPolicy` does exactly this to keep replies off an unpublished article).

**Policy registration must happen in `boot()`, not `register()`.** `CommentPolicyRegistry` is a singleton bound in `CommentServiceProvider::register()`. Other domains must register their policies in their own provider's `boot()` to ensure the singleton already exists.

**Editor assets must load on the list shell, not on fragments.** Reply/edit `<x-editor::rich-text>` instances often render only inside `GET /comments/fragments` HTML or when `canCreateRoot` is false — both paths discard `@push`. `comment-list.blade.php` includes `@include('editor::components._assets')` when `!$isGuest && !$error`; Alpine calls `initQuillEditor` when Répondre/Éditer opens. See README § Editor assets and inline composers.

**`CommentMaintenancePublicApi::deleteFor()` is a hard soft-delete on all comments for a target.** It uses `deleteByTarget()` on the repository, which applies Laravel soft deletes to roots and replies in one query. Call this when deleting the owning entity (e.g. a chapter), not when moderating individual comments.

**Moderation actions emit distinct events.** `emptyContentByModeration` emits `CommentContentModerated`; `deleteByModeration` emits `CommentDeletedByModeration`. Story domain listens to `CommentDeletedByModeration` to revoke chapter credits.

## Registry integrations

- **CommentPolicyRegistry** (this domain) — other domains call `register(entityType, policy)` in their `boot()` to enforce domain-specific comment rules.
- **ModerationRegistry** (`Moderation` domain) — registered as topic `'comment'` with `CommentSnapshotFormatter` so moderators can view and act on reported comments.
- **EventBus** (`Events` domain) — all four comment events are registered in `CommentServiceProvider::boot()`.
