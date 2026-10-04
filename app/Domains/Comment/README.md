# Comment Domain

This domain provides a pluggable comment system with per-entity policy registry. It is designed to be consumed by other domains (Story, News, etc.) rather than to serve a standalone UI.

## Architecture overview

Comments are stored in a single `comments` table using a polymorphic-style `commentable_type` / `commentable_id` pair. This allows any entity in any domain to receive comments without requiring a schema change in this domain.

Replies are one level deep only: a reply's `parent_comment_id` must point to a root comment. Nesting beyond one level is rejected by the API.

A root comment may also carry **annotations** — remarks anchored on a passage of the commented text, posted together with it (see [Annotations](#annotations)). Only chapters enable them today.

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
| `deleteFor(entityType, entityId)` | Force-deletes all comments (roots and replies) for a given target; their annotations go with them through the `comment_id` FK cascade. Returns affected row count. |

### AnnotationPublicApi

Reads and acts on the annotations of one root comment. The first annotations are created by `CommentPublicApi::create` with their root comment; later ones go through `saveChanges`.

| Method | Description |
|--------|-------------|
| `saveChanges(commentId, byUserId, AnnotationChangeSetDto)` | The commenter's adds / edits / deletes under their own root comment, in one transaction; returns the refreshed `AnnotationListDto`. 404 for an unknown or trashed comment; 403 unless the user wrote this root comment and `canAnnotate` holds. Every item error is collected into one 422 keyed `adds.<clientKey>` / `edits.<id>` / `deletes.<id>`; an id that is not a live root of this comment written by the user (deleted, foreign, reply) is `errors.stale`, never a 403. An edit resets the processed flag and leaves the anchor untouched; a delete takes the replies with it. No event. |
| `getForComment(commentId, viewerId)` | `AnnotationListDto` of the root annotations the viewer may see, with per-row action flags. 404 (`ModelNotFoundException`) for an unknown or trashed comment, 403 (`AuthorizationException`) for a viewer who may see none. |
| `setProcessed(annotationId, byUserId, value)` | Author / co-author toggle of the processed flag. 403 unless the user resolves to the `author` role on that comment (an author who is also the commenter does not); 422 on a reply row. |
| `moderatorDelete(annotationId, byUserId)` | Soft-deletes one annotation and its replies. No role check inside: the route's `role` middleware is the gate. |

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
| `supportsAnnotations(entityType)` | Does the entity type carry annotations at all? Gates the annotation UI and script on the comment list (default `false`) |
| `canAnnotate(entityType, entityId, userId)` | Can the user attach annotations to their root comment? (default `false`: entity not annotatable) |
| `canMarkAsProcessed(entityType, entityId, userId)` | Is the user an author of the entity — sees every annotation, may mark them processed? (default `false`) |
| `getAnnotationBodyMaxLength(entityType)` | Max plain-text length of an annotation body (default 1000) |
| `getAnnotationHighlightMaxLength(entityType)` | Max length of the highlighted passage (default 500) |

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
| `CommentToCreateDto` | Input for `create()` — entity type, entity ID, body, optional parent comment ID, optional list of `AnnotationToCreateDto` |
| `AnnotationChangeSetDto` | Input for `saveChanges()` — `adds` (`AnnotationToCreateDto[]`, each with its `clientKey`), `edits` (id => body), `deletes` (ids) |
| `CommentDto` | A single comment with author profile, permission flags (`canReply`, `canEditOwn`), `annotationCount` (root annotations **the viewer** may see), and nested children |
| `AnnotationListDto` / `AnnotationDto` | Payload of `getForComment`: the viewer's role (`commenter`, `author`, `moderator`) and the rows, each with `highlighted_text`, sanitized `body`, `is_processed` (null for the commenter), `can_mark_as_processed`, `can_delete` |
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
| `Auth::UserDeleted` | Nullifies `author_id` on all comments by that user (content is preserved), and on their annotations — trashed ones included |
| `Auth::UserDeactivated` | Soft-deletes all comments by that user |
| `Auth::UserReactivated` | Restores soft-deleted comments by that user |

## Blade component

`<x-comment::comment-list :entityType="..." :entityId="..." :perPage="5" :page="1" />`

The `CommentListComponent` supports two loading modes:

- **Eager** (`page >= 1`): loads the requested page immediately on server render.
- **Lazy** (`page <= 0`): returns metadata and total count only; client triggers fragment loading via an Intersection Observer.

It also supports **deep linking**: when the request contains a `?comment={id}` query parameter, the component pre-loads pages until the target comment is found, then passes a `targetCommentId` to the Blade template for client-side scroll-and-highlight.

### Selection toolbar (`<x-comment::annotable>`)

`<x-comment::annotable>` wraps content in an annotable region and renders a
selection toolbar template, shown near any text selection inside the region
when `canAnnotate` is true. Other domains contribute buttons through the
`toolbar-actions` slot; each button owns its own Alpine bindings.

An action may carry `data-requires-selection-within="<css selector>"` (on the
slot's top-level element or a descendant of it). It is then shown only when
every non-blank text the selection covers lies inside an element matching the
selector (a boundary that merely touches a block without covering its text, e.g.
a triple-click ending at `(nextBlock, 0)`, is ignored — Shared's
`anchoring/text-range.js`). If the declaring element also carries the boolean
`data-requires-single-area`, that text must lie in **one single** matching
element: a selection spanning two of them hides the action. Actions without the
attribute are always shown. When no
action applies, the toolbar is not shown at all. The selector is chosen by the
contributing domain — Comment never knows which domain declared it.

### Editor assets and inline composers

Reply and edit composers use `<x-editor::rich-text>`, but they often appear only
inside HTML returned by `GET /comments/fragments` (lazy load) or when
`canCreateRoot` is false (no root composer on the page). A `@push` executed
while rendering an AJAX fragment is discarded — there is no layout to flush the
stack into.

The list shell therefore includes `@include('editor::components._assets')` when
`!$isGuest && !$error`, so Editor Vite entries are on the full-page stack before
any fragment arrives. The `@once` guard on `_assets` keeps a single load when
the root composer is also present.

Alpine on the list calls `window.initQuillEditor` when **Répondre** or
**Éditer** opens (double `requestAnimationFrame`, same pattern as post-fragment
append). `CommentListEditorAssetsTest` guards asset emission; browser coverage is
in `e2e/tests/core/chapter-comments.spec.ts`.

### Draft autosave and post-submit clear

Compose forms opt into `app/Domains/Comment/Resources/js/comment-draft/index.js`
via `data-comment-draft`. Drafts live in `localStorage` and restore on the next
visit. On successful create, the controller flashes `comment.draft_consumed`; the
list shell sets `window.__commentDraftConsumed` **before** the deferred Vite
module boots. Bootstrap clears the matching slot and skips restore — otherwise
hosts that keep the root form visible (news: unlimited roots) re-show the
just-posted body. Chapters hide the root form after one root, so the same race
was invisible there.

The same key (`comment-drafts:{userId}:{entityType}:{entityId}`) also holds the
`annotations` slot: chapter annotation drafts `{ tempId, body, highlighted,
prefix, suffix }`, posted with the root comment. `window.commentDrafts` exposes
`listAnnotations`, `addAnnotation` (returns the item with a generated `tempId`),
`updateAnnotation`, `removeAnnotation` and `clearAnnotations`; each mutation
fires a `comment-drafts:annotations-changed` window event with
`{ entityType, entityId, count }`. Malformed items are dropped on load. A `root`
consumed marker clears `root` **and** `annotations`; a `reply` marker clears only
`reply`. The key is removed once every slot is empty.

A fourth slot, `annotationChanges` — `{ adds: [{ tempId, body, highlighted,
prefix, suffix }], edits: { [id]: body }, deletes: [id] }` — holds pending
changes to the annotations of an already-posted root comment, saved in one
`PUT /comments/{id}/annotations` (`saveAnnotationChanges` in
`annotations/api.js`, which maps `tempId` to the `key` the server echoes in
`adds.<key>` errors). `window.commentDrafts` exposes `getAnnotationChanges`,
`countAnnotationChanges`, `addPendingAnnotation` (returns the `tempId`),
`updatePendingAdd`, `removePendingAdd`, `setPendingEdit`, `undoPendingEdit`,
`setPendingDelete` (also drops a pending edit of that id), `undoPendingDelete`
and `clearAnnotationChanges`; each write fires
`comment-drafts:annotation-changes-changed` with `{ entityType, entityId, count }`.
The root consumed marker leaves this slot alone. The payload is schema version 2;
version-1 payloads still load, with an empty `annotationChanges`. Vitest:
`comment-draft/index.test.js`. Browser:
`e2e/tests/core/comment-draft-consume.spec.ts`.

## Annotations

An annotation is a remark on one passage of the commented text: the highlighted
passage (plain text, plus a short `prefix`/`suffix` for re-anchoring) and a
short rich body. It is feedback to the entity's authors, attached to the
reader's **root comment**, and has no life of its own:

- **Created only with the root comment.** The reader collects drafts in the
  browser, then `POST /comments` sends them with the root body; both are written
  in one transaction or not at all. There is no endpoint to add, edit or reply to
  an annotation afterwards, so a reader who already has a root comment on the
  entity gets no « Annoter » action. `comment_annotations.parent_annotation_id`
  exists for replies, which no code path creates yet.
- **No events.** `CommentPosted` fires once for the root comment; credits and
  notifications ignore annotations.
- **Opt-in per entity type** through `CommentPolicy::supportsAnnotations` (type
  level: chapters yes, news no), then per user through `canAnnotate` (chapters:
  the same audience as a root comment, never a guest).

### Who sees what

`AnnotationAccessService` resolves the viewer of a root comment to one role:

| Role | Who | Sees | May |
|------|-----|------|-----|
| `commenter` | author of the root comment | their own annotations, **without** the processed flag | read |
| `author` | `canMarkAsProcessed` is true (chapter authors and co-authors, not beta readers) | all annotations and the processed flag | mark processed / not processed |
| `moderator` | moderator, admin, tech-admin | all annotations and the processed flag (in the JSON; the pop-up does not display it) | delete one |

Anyone else gets a 403 on the list and a count of 0. The commenter wins over
author: an author who commented on their own entity is a commenter there. The
same rule drives `CommentDto::annotationCount`, computed for a whole page in one
grouped query (`visibleCounts`).

### Front-end

All JavaScript is in `Resources/js/annotations/` (one Vite entry, `index.js`),
pushed with `@pushOnce('head-scripts', 'comment-annotations-bundle')` by every
component that needs it, so it is emitted once. `comment-list` pushes it — and
renders the drafts banner and the server pop-up — only for an authenticated
viewer on an entity type whose policy `supportsAnnotations()`; news pages load
none of it. It loads in `<head>`, before the
comment-draft module, which is why the banner re-reads the drafts slot on
`DOMContentLoaded`.

- **« Annoter »** — `<x-comment::annotate-button :can-annotate>`, placed by the
  consumer in `<x-comment::annotable>`'s `toolbar-actions` slot. It carries
  `data-requires-selection-within=".ce-block--text"` (text blocks only; images,
  captions and chapter-choice blocks are excluded) plus
  `data-requires-single-area` (hidden when the selection spans two text blocks;
  Quote's « Citer » does not carry it), and only dispatches
  `annotation:open-form`: the slot is cloned from a `<template>`, so it cannot
  host the form.
- **Reactions ❤️ 🔥 👍** — `<x-comment::reaction-buttons :entity-type
  :can-annotate>`, placed right after « Annoter » in the same slot, same
  `data-requires-selection-within` + `data-requires-single-area` rules
  (`reactions.js`, Alpine `annotationReactions`). A click stores `<p>EMOJI</p>`
  anchored to the selection, with no form, and clears the selection; a
  multi-block or over-cap selection is ignored (defence in depth: the toolbar
  already hides the buttons on a multi-block one). French `aria-label`s.
- **Draft vs pending mode** — the annotable and the form carry
  `data-annotation-mode`: `draft` (the viewer has no root comment yet; writes go
  to the comment-draft `annotations` slot) or `pending` (a root exists, its id in
  `data-root-comment-id`; writes go to `annotationChanges.adds`). The form and
  the reactions both follow it.
- **Capture form** — `<x-comment::annotation-form :entity-type :entity-id
  :annotation-mode :root-comment-id>`,
  rendered by the consumer **outside** the annotable region and teleported to
  `body` (`capture-form.js`). Quill with the Editor `inline` preset (bold,
  italic, custom emoji); limits read from the policy registry. A selection that
  spans two text blocks, or exceeds the highlight limit, opens the form with an
  inline error and Save disabled. Save (button or Ctrl/Cmd+Enter) stores a draft
  or a pending add, per the mode; Escape or a click outside discards.
  The window event `annotation:open-edit` `{ tempId }` reopens it on a draft (or
  pending add); `annotations:edit-saved-row` `{ id, body, highlighted }` opens it
  on a saved annotation, body only, and stores a pending edit.
- **Drafts banner and pop-up** — `partials/annotation-banner.blade.php`, inside
  the root-comment form (`drafts.js`, Alpine `annotationDrafts`). Shows
  « N annotations, écrivez votre commentaire… » while the slot is not empty and
  opens the `annotation-drafts` modal (edit / delete each draft). On submit it
  writes the slot into the hidden `annotations` input; the `root` consumed
  marker then clears the slot. A refused post keeps both the drafts and the typed
  root body.
- **« N annotations » and the server pop-up** — `comment-item` shows the button
  when `annotationCount > 0`; it dispatches `annotations:open` `{ commentId }`
  to the one `annotation-modal` partial per list (`modal.js` + `api.js`, Alpine
  `annotationsModal`, modal `annotations-server`). The list is fetched on first
  open and cached; toggling or deleting updates the cached rows in place and the
  button's label (hidden at 0). The highlighted passage is rendered as text
  (`x-text`), the body as sanitized HTML. Buttons follow the per-row
  `can_mark_as_processed` / `can_delete` flags; the processed marker is shown
  to the `author` role only.
- **Commenter overlay in the pop-up** — for `viewer_role === 'commenter'` the
  rendered rows (`rows`) overlay the `annotationChanges` slot on the cached
  server rows: a pending edit shows its pending body flagged « Modifiée — non
  enregistrée », a pending delete « Sera supprimée », and pending adds follow
  as « Ajoutée — non enregistrée » rows (shown even when the server list is
  empty). Rows with `can_edit` get « Modifier » (dispatches
  `annotations:edit-saved-row` for the capture form; never offered on a row
  pending deletion) and « Supprimer » (a pending delete; `confirm()` first when
  the row has replies). Every pending row has « Annuler la modification ». The
  pop-up re-reads the slot on `comment-drafts:annotation-changes-changed`,
  shows the banner's `annotations:save-errors` on the matching row with
  « Retirer » (undoes that item), and replaces its cached list on
  `annotations:list-refreshed`. A pending edit / delete whose annotation is no
  longer in the loaded server list (deleted by a moderator, then the page
  reloaded) gets a row of its own, before the pending adds, already flagged
  with `errors.stale` and « Retirer », so it can be dropped without « Tout
  annuler ». Authors and moderators see none of it.

Browser coverage: `e2e/tests/core/chapter-annotations.spec.ts` (before the root
comment) and `e2e/tests/core/chapter-annotation-round-trip.spec.ts` (reactions,
pending changes, save banner, replies).

### Lifecycle

| Event | Annotations |
|-------|-------------|
| Moderator empties the root comment | Soft-deleted (the comment stays, with the default text) |
| Moderator deletes the root comment | Gone: the comment is force-deleted, the `comment_id` FK cascades |
| Moderator deletes one annotation | That row and its replies soft-deleted |
| Owning entity deleted (`CommentMaintenancePublicApi::deleteFor`) | Gone through the same cascade |
| User deleted | Kept, `author_id` set to null (trashed rows too); authors still see them |
| User deactivated / reactivated | No row change: the root comment's own soft delete hides them and its restore shows them again, so an annotation a moderator removed never comes back |

### Known gaps

- `visibleCounts` gives authors and moderators a count without asking the
  entity's `canAnnotate`. Harmless while no non-annotatable entity can hold
  annotation rows (creation checks `canAnnotate`).
- Reporting an annotation on its own is not supported: reports target the root
  comment.

## Routes

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/comments` | `auth`, `compliant` | Create a comment or reply |
| `PATCH` | `/comments/{commentId}` | `auth`, `compliant` | Edit own comment |
| `POST` | `/comments/{commentId}/empty-content` | Moderator+ | Replace body with default text |
| `DELETE` | `/comments/{commentId}` | Moderator+ | Hard-delete comment and its replies |
| `GET` | `/comments/fragments` | public | Return HTML fragment for lazy-load pagination |
| `GET` | `/comments/{commentId}/annotations` | `auth`, `compliant` | JSON list of the root comment's annotations visible to the viewer (403 / 404 otherwise) |
| `PUT` | `/comments/{commentId}/annotations` | `auth`, `compliant` | Body `{ adds: [{key, body, highlighted_text, prefix, suffix}], edits: [{id, body}], deletes: [id] }`; the root comment's author only. 200 + list, 422 keyed per item |
| `PUT` | `/comments/annotations/{annotationId}/processed` | `auth`, `compliant` | Body `{ value: bool }`; author / co-author only |
| `DELETE` | `/comments/annotations/{annotationId}` | Moderator+ | Soft-delete one annotation and its replies |

`POST /comments` accepts an optional `annotations` field: a JSON string (one
hidden input) holding a list of `{ body, highlighted_text, prefix?, suffix? }`.
`StoreCommentRequest` decodes and shape-checks it (`highlighted_text` ≤ 500,
`prefix`/`suffix` ≤ 255); `CommentPublicApi::create` then refuses it on a reply
or when the policy's `canAnnotate` is false, and checks each body (1 to
`getAnnotationBodyMaxLength` plain chars under the `annotation` sanitizer
profile) and highlight (`getAnnotationHighlightMaxLength`). Any refusal rejects
the whole post with one error under `annotations`, nothing written. Otherwise
the root comment and its annotations are inserted in one transaction, and
`CommentPosted` fires once, after it — credits and notifications do not depend
on the annotation count.

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

### `comment_annotations` table

One row per annotation. `comment_id` always points to the **root comment**
(reply rows too) with an FK that cascades on delete; `parent_annotation_id` is
null for a root annotation (FK to the same table, cascading). `author_id` has no
FK (cross-domain), like `comments`. `highlighted_text`, `prefix` and `suffix`
are text (unsanitized, see below); `body` is sanitized HTML. `is_processed` / `processed_at` are
the authors' flag; soft deletes. Indexes: `comment_annotations_tree_index` on
`(comment_id, parent_annotation_id, deleted_at)` — named explicitly because the
generated name exceeds MySQL's 64 characters — and `author_id`.

## Body sanitization

All bodies pass through `CommentBodySanitizer`, which runs HTMLPurifier before persistence. Length checks operate on the plain-text length (after stripping tags) of the sanitized output. Two profiles, both in `config/purifier.php`:

| Profile | Used for | Allows |
|---------|----------|--------|
| `strict` | comment and reply bodies | the comment editor's formatting |
| `annotation` | annotation bodies | `p`, `br`, `strong`, `em` and the custom-emoji `span` classes only |

The highlighted passage (and `prefix` / `suffix`) is **not** sanitized: it is stored as submitted and must always be rendered as text (`x-text`, `{{ }}`), never as HTML.

## Moderation integration

The domain registers a `comment` topic with the `ModerationRegistry`. When a comment is reported, moderators see a formatted snapshot rendered by `CommentSnapshotFormatter` and can act via the moderation admin panel (empty content or delete).
