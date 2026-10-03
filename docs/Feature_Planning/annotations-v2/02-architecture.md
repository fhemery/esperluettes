# Chapter annotations — v2 (writer side) — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** The file-by-file list of
> edits belongs to `03-plan.md`.

- Functional spec: [`01-functional.md`](./01-functional.md)
- v1 record: [`_done/annotations.md`](../_done/annotations.md)

## 1. Domain placement

**`Comment`**, extending v1 in place. The `comment_annotations` table, the
model, `AnnotationService`, `AnnotationAccessService`, `AnnotationPublicApi`,
the pop-up and the comment-draft store all already live there; v2 adds
behaviour on the same rows. The schema already has `parent_annotation_id`
(replies), `is_processed` / `processed_at` and soft deletes. **No migration.**

### 1.1 Changes in other domains

**Story** (direct calls + implementation of the existing policy contract, no new
extension point):

- `ChapterCommentPolicy::canAnnotate` changes meaning: *a logged-in user, not an
  author/co-author, who may comment the chapter*. It no longer requires "no root
  comment yet" (lifts v1 A5; decision #6: an emptied root still exists, so it
  still qualifies). The "posted together with a new root" path stays guarded by
  `canCreateRoot`, already checked in `CommentPublicApi::create`.
- `ChapterController::show` tells the annotable which **mode** the toolbar
  writes into: `draft` (no root comment yet) or `pending` (root comment
  exists), plus that root comment's id. Both come from
  `CommentPublicApi::userHasRoot` / an id lookup the policy already performs. No new
  Story → Comment edge.
- `chapters/show.blade.php` renders the three emoji buttons in the toolbar
  `toolbar-actions` slot, beside « Annoter », under the same `$canAnnotate`
  gate and the same `data-requires-selection-within` rule.

**News**: `NewsCommentPolicy::canAnnotate` stays `false`. Nothing else changes.

No other domain is touched. No events, notifications, statistics or moderation
topics (decision #3, A4).

## 2. Data model

### 2.1 Tables

Unchanged: `comment_annotations` from v1. Usage in v2:

| Column | v2 use |
|--------|--------|
| `parent_annotation_id` | `NULL` = root annotation; set = **reply** (one level: a reply's parent is always a root) |
| `comment_id` | always the root comment, also on replies |
| `author_id` | writer of the row (commenter for roots; author/co-author or commenter for replies) |
| `highlighted_text` / `prefix` / `suffix` | roots only; `NULL` on replies. Immutable after creation. |
| `is_processed` / `processed_at` | roots only; reset to `false` / `NULL` when the commenter edits the body (decision #4) |
| `body` | sanitized with the `annotation` purifier profile (roots and replies alike) |

The existing `comment_annotations_tree_index (comment_id, parent_annotation_id, deleted_at)`
serves both the roots query and the replies query. `author_id` is indexed.

### 2.2 Model

`CommentAnnotation` unchanged (attributes, casts, `parent`, `replies`, `roots()`,
`repliesOnly()` scopes all exist).

### 2.3 Lifecycle rules

| Trigger | Rule |
|---------|------|
| Commenter saves a delete | Soft-delete the root and its replies in one transaction (same as moderator delete, v1). |
| Commenter saves an edit | Update `body`; reset `is_processed`/`processed_at`; replies untouched; anchor columns untouched. |
| Writer deletes own reply | Soft-delete that reply. |
| Moderator deletes a reply | Existing moderator delete: soft-deletes the row (a reply has no children). |
| Moderator empties root / deletes root / chapter deleted / user deleted | Unchanged from v1. `nullifyAuthor` already covers replies (same table). |
| User deactivated | **No row touched** (decision #10). Replies are filtered **at read time**: a reply whose `author_id` belongs to an inactive user is not returned. Roots are already hidden via the root comment's soft delete (v1). |

## 3. PHP architecture

### 3.1 Public API

`AnnotationPublicApi` gains:

```php
/**
 * Apply a commenter's pending changes under their own root comment, atomically.
 * @param AnnotationChangeSetDto $changes adds + edits + deletes
 * @return AnnotationListDto the viewer's list after the save
 * @throws ModelNotFoundException  root comment unknown/trashed
 * @throws AuthorizationException  viewer is not the root comment's author, or canAnnotate is false
 * @throws ValidationException     any item invalid or stale; keys name the item (see §3.5)
 */
public function saveChanges(int $commentId, int $byUserId, AnnotationChangeSetDto $changes): AnnotationListDto;

/**
 * @throws ModelNotFoundException  parent unknown/trashed
 * @throws AuthorizationException  viewer may not reply (decision #5)
 * @throws ValidationException     body empty/too long, or parent is itself a reply
 */
public function reply(int $parentAnnotationId, int $byUserId, string $body): AnnotationDto;

/**
 * Writer deletes their own reply.
 * @throws ModelNotFoundException | AuthorizationException (not the writer, or not a reply)
 */
public function deleteOwnReply(int $replyId, int $byUserId): void;
```

`getForComment` keeps its signature; its `AnnotationDto::$replies` is now
populated (same DTO shape, `replies: []` on reply items), and each DTO gains:

| New field | Meaning |
|-----------|---------|
| `canEdit` (root) | viewer is the commenter (owns the row) |
| `canReply` (root) | §3.3 reply rule for this viewer on this root |
| `canDelete` | now also `true` for the writer of a **reply** (own reply); unchanged for moderators |

`moderatorDelete` is unchanged and already works on a reply id.

New DTOs in `Public/Api/Contracts/`:

- `AnnotationChangeSetDto` — `adds: AnnotationToCreateDto[]` (each carrying a
  client `key`), `edits: array<int id, string body>`, `deletes: int[]`.
  `AnnotationToCreateDto` gains an optional `?string $clientKey` (null on the
  v1 create-with-root path).

### 3.2 Services

- `AnnotationService` gains: `applyChanges(int $commentId, int $authorId, AnnotationChangeSetDto)`
  (single `DB::transaction`: deletes → edits → adds), `createReply`,
  `getRepliesForRoots(int[] $rootIds)` (one query, oldest first),
  `deleteReply`. Body sanitizing reuses `CommentBodySanitizer::ANNOTATION`.
- **Item validation is extracted** from `CommentPublicApi::validateAnnotations`
  into a shared private validator (body 1–max plain chars, highlight ≤ policy
  max, prefix/suffix ≤ 255), used by both the create-with-root path and
  `saveChanges`. This also removes the hard-coded `max:500` from
  `StoreCommentRequest` (A5 leftover): the request keeps only shape rules; the
  highlight cap comes from the policy.
- `AnnotationAccessService`:
  - `visibleCounts` returns all zeros when the type's
    `supportsAnnotations()` is false, for every viewer (A5 leftover: counts
    respect the type gate; v1 only gated the rendering).
  - new `canReply(string $viewerRole, CommentAnnotation $root, Collection $visibleReplies, int $viewerId): bool`.
  - new read-time filter for deactivated reply writers, via
    `AuthPublicApi::getUsersById` (one call per pop-up open, distinct reply
    writer ids only).

Controllers call `AnnotationPublicApi` only, as in v1.

### 3.3 Policy / authorization

All enforced **server-side** in `AnnotationPublicApi` / `AnnotationAccessService`;
the pop-up flags (`canEdit`, `canReply`, `canDelete`) are display hints only.

| Action | Rule |
|--------|------|
| `saveChanges` | viewer = root comment's author **and** `canAnnotate(type, entityId, viewer)`. Every edit/delete id must be a live **root** annotation of **that** comment, written by the viewer. Adds are validated as v1 items. |
| `reply` as author | `canMarkAsProcessed(type, entityId, viewer)` (author/co-author, not beta) — on any live root. |
| `reply` as commenter | viewer = root annotation's `author_id` **and** at least one **visible** reply under it whose writer ≠ the root's writer (§7 #2). |
| `reply` otherwise | 403. Moderators never (resolved role `moderator` → 403). |
| `deleteOwnReply` | row is a reply and `author_id` = viewer. |
| Moderator delete | unchanged (role middleware). |

Role precedence (commenter → author → moderator) is unchanged. An author who
commented their own chapter cannot exist in v2 (`canCreateRoot` forbids it),
so "commenter" and "author" never collide on one root.

### 3.4 Events and listeners

None emitted. No new listener: deactivation is a read-time filter (decision
#10); deletion is covered by the existing `nullifyAuthor`.

### 3.5 Routes, controllers, form requests

All under the existing `web, auth, compliant` group, `comments.` prefix. **No
PATCH.**

| Verb | URI | Name | Controller | Request |
|------|-----|------|------------|---------|
| `PUT` | `/comments/{commentId}/annotations` | `comments.annotations.save` | `AnnotationController@save` | `SaveAnnotationChangesRequest` |
| `POST` | `/comments/annotations/{annotationId}/replies` | `comments.annotations.replies.store` | `AnnotationReplyController@store` | `StoreAnnotationReplyRequest` |
| `DELETE` | `/comments/annotations/replies/{replyId}` | `comments.annotations.replies.destroy` | `AnnotationReplyController@destroy` | — |

`PUT …/annotations` body:

```json
{
  "adds":    [{ "key": "tmp-…", "body": "<p>❤️</p>", "highlighted_text": "…", "prefix": "…", "suffix": "…" }],
  "edits":   [{ "id": 12, "body": "<p>…</p>" }],
  "deletes": [13, 14]
}
```

Responses: `200` with the refreshed `AnnotationListDto` JSON; `422` with errors
keyed **`adds.<key>`, `edits.<id>`, `deletes.<id>`** so the client marks the
offending pending item (A3); a stale id (deleted meanwhile) is a `422` on its
key, not a `404`; a trashed root comment is `404` (client marks every item stale).
Nothing is written on any error.

`POST …/replies` returns `201` with the reply's `AnnotationDto` JSON.

## 4. Frontend architecture

All JS stays in `Comment/Resources/js/annotations/` (+ the comment-draft store),
loaded by the existing `comment-annotations-bundle` entry, which is only pushed
for types where `supportsAnnotations()` is true.

- **Comment-draft store**: a new `annotationChanges` slot in the same
  per-(user, entityType, entityId) localStorage key (§7 #1): `{ adds: [{tempId, body, highlighted, prefix, suffix}], edits: {id: body}, deletes: [id] }`.
  Slot API on `window.commentDrafts` mirrors the v1 annotations slot, and its own
  change event (`comment-drafts:annotation-changes-changed`). The root consumed
  marker **does not** clear it (only a successful save does). Schema version
  bumped with a tolerant read: v1 payloads load with an empty slot.
- **Toolbar mode**: the annotable carries `data-annotation-mode="draft|pending"`
  and `data-root-comment-id`. The capture form and the emoji buttons write to the
  `annotations` slot (draft) or to `annotationChanges.adds` (pending).
- **Emoji buttons**: one small component rendered by Story in the
  `toolbar-actions` slot; a click reads the current selection with the shared
  `extractAnchor`, applies the same refusal rules as « Annoter »
  (`data-requires-selection-within`, highlight cap), writes `<p>EMOJI</p>`
  directly into the active slot and clears the selection. Accessible labels from
  lang files.
- **Save banner**: a new Alpine component `annotationChangesBanner`, rendered by
  `comment-list` **outside** the root form (which is absent once a root exists),
  `position: sticky; bottom: 0`, `role="status" aria-live="polite"`. Shows the
  plural-aware count, « Enregistrer » (one `PUT`) and « Tout annuler ». On 422 it
  dispatches the error keys so the pop-up marks the items; on 200 it clears the
  slot and hands the returned list to the pop-up cache.
- **Pop-up** (`annotationsModal`): for the commenter, overlays the
  `annotationChanges` slot on the server rows (« Modifiée — non enregistrée »,
  « Sera supprimée », per-row undo) and lists pending adds; « Modifier » reopens
  the capture form in edit mode on the body only; « Supprimer » on a row with
  replies asks for confirmation first. For everyone, renders each root's reply
  thread, « Répondre » (inline editor, `inline` preset, same limits) when
  `can_reply`, own-reply « Supprimer » (confirm), moderator « Supprimer » as
  v1. After an author's reply, a one-line hint about replying on the root
  comment.
- All strings in `Comment/Private/Resources/lang/fr/annotations.php`.

Blade-first is kept where v1 had it (banner shell, buttons); the pop-up content
stays JS-rendered from JSON, as in v1.

## 5. Deptrac

**No new edge.** Comment → Auth Public (`AuthPublicApi`) already exists
(`AnnotationAccessService`); Story → Comment Public (policy, `CommentPublicApi`)
already exists.

## 6. Testing strategy

- **Integration (PHP feature tests, default):**
  - `saveChanges`: success (adds/edits/deletes in one call, processed reset on
    edit, replies removed with a deleted root); atomicity (one stale id → nothing
    written, error key names it); ownership (other user's id, a reply id, an id
    under another comment → 422/403); not the root's author → 403; works after
    the root was emptied; refused when `canAnnotate` is false (News).
  - replies: author/co-author may reply; beta reader 403; commenter 403 before
    an author reply and 200 after; moderator 403; parent is a reply → 422;
    own-reply delete; another's reply → 403; moderator delete of a reply.
  - list: replies included for commenter/author/moderator; a deactivated
    writer's reply hidden, back after reactivation; `can_edit` / `can_reply` /
    `can_delete` per role.
  - v1 leftovers: highlight cap read from the policy; counts respect the gate;
    beta reader sees count 0.
  - Story: `canAnnotate` true with an existing (or emptied) root; toolbar renders
    emoji buttons and the right `data-annotation-mode`.
- **Vitest:** `annotationChanges` slot (add/edit/delete/undo, v1 payload
  tolerance, consumed marker leaves it alone); emoji-button capture into the
  right slot; banner (count, save success, 422 mapping); modal overlay and
  reply rendering.
- **VERIFY only:** sticky banner on phone not covering submit buttons; toolbar
  emoji on touch; screen-reader labels; full commenter ↔ author round trip.
  Reuse or prune v1's orphan e2e helpers (A5).

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where pending post-publish changes live in the browser | **A** new `annotationChanges` slot in the comment-draft store · **B** separate module + localStorage key | **A** (user) | One per-user/per-chapter storage scheme, one versioning and change-event model; the consumed marker is taught to leave the slot alone. |
| 2 | How "an author has replied" is decided | **A** derived: a visible reply whose writer ≠ the root's writer · **B** `is_author_reply` column set on create | **A** (user) | Only authors and the commenter can reply, so the writer identifies the side; no migration, no policy call. Accepted: a removed co-author's reply still counts; deleting/hiding the only author reply re-locks the commenter. |
| 3 | Shape of the atomic save | operation list (adds/edits/deletes) · declarative "final state" | operation list | Maps 1:1 to the pending items, so a 422 can name the offending item (A3). |
| 4 | Lifting A5 | change `canAnnotate` meaning · add a `canAnnotateAfterPublish` policy method | change `canAnnotate` | The create-with-root path is already guarded by `canCreateRoot`; one fewer contract method for News/Default. |
| 5 | Hiding a deactivated user's replies | read-time filter via `AuthPublicApi::getUsersById` · listener + soft delete | read-time filter | Decision #10 forbids touching rows; one call per pop-up open. |
| 6 | Commenter delete | soft delete with replies · hard delete | soft delete | Same as v1 moderator delete; consistent cascade/nullify behaviour. |

## 8. File layout

```
app/Domains/Comment/
  Public/Api/Contracts/
    AnnotationChangeSetDto.php
  Private/
    Controllers/AnnotationReplyController.php
    Requests/SaveAnnotationChangesRequest.php
    Requests/StoreAnnotationReplyRequest.php
    Support/AnnotationItemValidator.php        # extracted from CommentPublicApi
    Resources/views/components/reaction-buttons.blade.php   # <x-comment::reaction-buttons>, rendered by Story
    Resources/views/components/partials/annotation-changes-banner.blade.php
  Resources/js/annotations/
    changes-banner.js (+ .test.js)
    reactions.js      (+ .test.js)
    replies.js        (+ .test.js)             # reply thread helpers used by modal.js
  Tests/Feature/Annotations/
    SaveAnnotationChangesTest.php
    AnnotationRepliesTest.php
```

## 9. Risks acknowledged

- **Read-time deactivation filter** costs one Auth call per pop-up open. Revisit
  if reply threads get large or the pop-up is ever shown in-chapter (v3).
- **Derived author-reply rule** (§7 #2) ties "who may reply" to "who wrote a
  reply". If moderators or beta readers ever get reply rights, switch to a flag.
- **localStorage only**: pending changes do not follow the user across devices
  (out of scope); a cleared browser loses them silently.
- **Stale anchors**: edits never re-anchor; chapter text edits can leave
  highlights orphaned until v3 re-anchoring.
