# Comment Domain — Agent Instructions

- README: [app/Domains/Comment/README.md](README.md)

## Public API

- [CommentPublicApi](Public/Api/CommentPublicApi.php) — CRUD and read operations; main entry point for all comment interactions. Requires authenticated user for write and read. Delegates body sanitization to `CommentBodySanitizer` and permission decisions to `CommentPolicyRegistry`.
- [CommentMaintenancePublicApi](Public/Api/CommentMaintenancePublicApi.php) — system-level bulk delete for use by owning domains (e.g. when a chapter is deleted).
- [AnnotationPublicApi](Public/Api/AnnotationPublicApi.php) — read the annotations of one root comment (filtered by viewer role), toggle the processed flag, moderator delete, plus the commenter's `saveChanges` (adds/edits/deletes in one transaction; `PUT /comments/{id}/annotations`) and the replies (`reply`, `deleteOwnReply`). First annotations are created by `CommentPublicApi::create`: see invariants.
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
| `Auth::UserDeleted` | Nullifies `author_id` on all comments (content preserved) and on all annotations, trashed included |
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

**`CommentMaintenancePublicApi::deleteFor()` force-deletes all comments for a target.** It uses `deleteByTarget()` on the repository, which force-deletes roots and replies in one query; their annotations go through the `comment_id` FK cascade. Call this when deleting the owning entity (e.g. a chapter), not when moderating individual comments.

**Annotations have two write paths.** (1) `CommentPublicApi::create` → `CommentService::postComment` inserts the root comment and its first annotations in one transaction; annotation checks run first so a refusal lands on the `annotations` key and nothing is written; a reply carrying annotations is refused. (2) Once the root exists, `AnnotationPublicApi::saveChanges` applies the commenter's pending adds / edits / deletes atomically: 403 unless the viewer wrote this root comment and `canAnnotate` holds, every item error collected in one 422 keyed `adds.<clientKey>` / `edits.<id>` / `deletes.<id>`. An id that is not a live root of this comment by this user is `errors.stale`, never a 403. An edit resets the processed flag and keeps the anchor; a delete takes the replies. Keep the two paths on the shared item validator.

**Annotation replies are one level deep and role-gated.** `reply` targets a root annotation only (422 on a reply). Authors may reply on any root; the commenter only on their own root once a visible reply from someone else exists; moderators never. `deleteOwnReply` is for the writer only (a moderator deletes via the root). `can_edit` / `can_reply` / `can_delete` on `AnnotationDto` are display hints, recomputed by the API; always re-check server-side. `AnnotationAccessService::filterActiveReplyWriters` hides replies of deactivated (or unresolvable) writers at read time and feeds `can_reply`; anonymised replies (`author_id` null) stay visible. Do not read replies around it.

**The save banner and pop-up overlay are client state.** Pending changes live in the comment-draft `annotationChanges` slot; `annotationChangesBanner` (sticky, `changes-banner.js`) saves them in one PUT and emits `annotations:list-refreshed` / `annotations:save-errors`; the commenter's pop-up overlays the slot on the cached server rows. Annotating after a root exists uses `data-annotation-mode="pending"` (root id in `data-root-comment-id`), otherwise `draft`.

**Annotations emit no events.** `CommentPosted` fires once, after the transaction, and carries nothing about annotations; saves, replies, processed toggles and moderator deletes are silent. Credits, notifications and statistics must not depend on annotations.

**The processed flag is hidden from the commenter.** `AnnotationAccessService` resolves the viewer to `commenter`, `author` or `moderator` (in that order — an author commenting their own chapter is a `commenter`). For `commenter`, `getForComment` sends `is_processed: null`; authors and moderators receive it (moderators on purpose, though the pop-up displays it to authors only); only `author` may toggle it, only `moderator` may delete. Any new read path must go through `AnnotationAccessService`, not the raw policy, or it leaks the flag or other readers' annotations.

**Annotations are reachable only through their root comment.** Reads load the comment with the default soft-delete scope, and counts are computed for listed comments only. That is why deactivating/reactivating a user touches no annotation row: the root comment's soft delete hides them, its restore brings them back, and annotations a moderator soft-deleted stay deleted. Emptying a root comment by moderation soft-deletes its annotations (`emptyContentByModeration`).

**`highlighted_text`, `prefix` and `suffix` are stored unsanitized.** Only the body goes through HTMLPurifier (`annotation` profile). Always render the passage as text (`x-text`, `{{ }}`), never `x-html` / `{!! !!}`.

**The « Annoter » button cannot host the capture form.** `<x-comment::annotable>` clones its `toolbar-actions` slot from a `<template>` on each selection; the button only dispatches `annotation:open-form`. Toolbar actions opt into region limits with `data-requires-selection-within="<selector>"`; the boolean `data-requires-single-area` additionally hides the action when the selection spans two matching elements (« Annoter » and the reactions carry both). The consumer renders `<x-comment::annotation-form>` once, outside the annotable region.

**Moderation actions emit distinct events.** `emptyContentByModeration` emits `CommentContentModerated`; `deleteByModeration` emits `CommentDeletedByModeration`. Story domain listens to `CommentDeletedByModeration` to revoke chapter credits.

**Tests.** PHP under `Tests/`; Vitest next to the JS (`annotations/*.test.js`, `comment-draft/index.test.js`); browser: `e2e/tests/core/chapter-annotations.spec.ts` and `e2e/tests/core/chapter-annotation-round-trip.spec.ts` (reactions, pending changes, save banner, replies).

## Registry integrations

- **CommentPolicyRegistry** (this domain) — other domains call `register(entityType, policy)` in their `boot()` to enforce domain-specific comment rules. A policy implementing `CommentPolicy` directly must also answer the five annotation methods (`supportsAnnotations`, `canAnnotate`, `canMarkAsProcessed`, the two max lengths); extending `DefaultCommentPolicy` leaves the entity non-annotatable. `supportsAnnotations()` is type-level: false means `comment-list` renders no banner, no pop-up and pushes no annotations bundle.
- **ModerationRegistry** (`Moderation` domain) — registered as topic `'comment'` with `CommentSnapshotFormatter` so moderators can view and act on reported comments.
- **EventBus** (`Events` domain) — all four comment events are registered in `CommentServiceProvider::boot()`.
