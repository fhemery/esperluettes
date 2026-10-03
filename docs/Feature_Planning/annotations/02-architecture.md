# Chapter Annotations — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> **Revision 2 (2026-10-03).** The first version (June 2026, pre-loop) predated
> Quote's in-chapter work, the Editor domain, MultiEdit on chapters and the
> comment-draft store. This revision realigns the design on the code as it is
> today; superseded choices are kept in §7 with the reason they changed.

- Functional spec: [`01-functional.md`](./01-functional.md) — the long-term
  vision. **v1 is narrower** (§1.2); the spec stays the target for vNext.
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

## 1. Domain placement

Everything annotation-specific — table, model, services, public API, routes,
Blade, JS — lives in **Comment**. Annotations are sub-feedback under a root
comment: they reuse Comment's per-entity policy registry, body sanitizer,
moderation actions and user-lifecycle listeners, and stay reusable for any
future annotable entity without re-wiring Story.

### 1.1 Already in place (shipped by Quote and later tasks)

Treated as existing infrastructure; v1 builds on it and must not fork it.

| Brick | Where | What v1 uses |
|-------|-------|--------------|
| `<x-comment::annotable>` | anonymous component, `Comment/Private/Resources/views/components/annotable.blade.php` | Wraps the chapter article. Props `entityType`, `entityId`, `canAnnotate`, `viewerRole`, `maxSelection=500`; slot `toolbar-actions`. |
| Selection toolbar | `Comment/Resources/js/annotable/toolbar.js` | Shows the slot's actions on selection; per-action `data-requires-selection-within` hides non-applicable ones; too-long message above `maxSelection`. Actions read `window.getSelection()` themselves (Quote's pattern). |
| Shared anchoring | `Shared/Resources/js/anchoring/` | `buildCanonicalText(root, { within })`, `extractAnchor(range, root, canonical)`, `findAnchor` (`ok`/`moved`/`missing`), `closestBlock`, `trimRangeToText`. Context = 5 words each side; block boundary = `\n`. |
| Comment drafts | `Comment/Resources/js/comment-draft/index.js` | Key `comment-drafts:{userId}:{entityType}:{entityId}`, state `{ version, root, reply, annotations: [] }` — the `annotations` slot is reserved for this feature. Cleared on successful post by the flashed `comment.draft_consumed` marker. |
| Rich-text editor | Editor domain, `<x-editor::rich-text toolbar="<preset>">` | Annotation body editor; presets resolved by `Editor/Private/Support/ToolbarPresets`. |

Consequences: the old plan's phases 1 (editor), 2 (root-comment draft), 3 (JS
tests), 9 (anchoring) and 10 (annotable component) are done. The old
`<x-shared::editor :toolbar=[…]>` contract (former §7) is obsolete.

### 1.2 v1 scope

**In v1** — the core loop, no in-chapter display:

- On a chapter, a reader selects text inside **one text block** → the shared
  toolbar shows **« Annoter »** next to « Citer » → a small form with a minimal
  editor (bold, italic, emoji) → **Save** stages a draft in local storage.
- Above the root-comment editor, a banner *« {N} annotations, écrivez votre
  commentaire pour les sauvegarder »* with **« Voir les annotations »** opens the
  pop-up in **drafts mode** (edit / delete each draft).
- Submitting the root comment posts the drafts with it, atomically.
- After publish, an **« N annotations »** button above the root comment opens
  the pop-up in **server mode**, scoped to that commenter:
  - commenter (own comment): read-only;
  - chapter author / co-author: **Marquer comme traitée / non traitée**;
  - moderator: **Supprimer l'annotation**.

**Out of v1** (vNext, roadmap in §10): quick-emoji reactions; any in-chapter
display (tint, gutter, popover, filter menu) and therefore client-side
re-anchoring; post-publish add/edit/delete; replies; **per-annotation Report**
(decision #3 — report the root comment); moderator « Vider le contenu » on an
annotation; image annotation.

A direct consequence worth stating: in v1 annotations only travel with a new
root comment, so a reader who **already posted** their root comment on the
chapter cannot annotate it any more — « Annoter » is not rendered for them.

### 1.3 Changes in other domains

- **Story** — the chapter page and `ChapterCommentPolicy` only.
  - `chapters/show.blade.php`: adds Comment's annotate button to the existing
    `toolbar-actions` slot beside `<x-quote::toolbar-button>`, and binds
    `<x-comment::annotable :can-annotate>` to "can quote **or** can annotate"
    instead of the quote permission alone (today an annotation-only viewer —
    e.g. a moderator who cannot quote — would get no toolbar).
  - `ChapterCommentPolicy` implements the new annotation methods of
    `CommentPolicy` (§3.3). Direct implementation of an existing extension
    point; no new edge.
- **Editor** — one new capability-named preset in `ToolbarPresets`: **`inline`**
  = `bold`, `italic`, `custom-emoji` (Editor's own rule: add a preset, never an
  inline array in a consumer view). Comment uses it through Blade only.
- **Quote** — none. Its toolbar button, mini-form and highlight renderers are
  untouched; annotations add no `<mark>` in v1.

## 2. Data model

### 2.1 Table `comment_annotations` (roots + replies, one table)

Owned by Comment. Sized for the full spec so vNext (replies, post-publish edits)
needs no schema change; v1 only ever writes root rows.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `comment_id` | unsignedBigInteger, FK `comments.id` `cascadeOnDelete` | Always the **root** comment. Denormalised onto reply rows so one `WHERE comment_id IN (…)` returns the whole tree. Same-domain FK. |
| `parent_annotation_id` | unsignedBigInteger, nullable, FK self | `NULL` = root annotation; set = reply (vNext; one level deep, enforced by the service). |
| `author_id` | unsignedBigInteger, nullable | No cross-domain FK; nullified on user deletion (same as comments). |
| `body` | text | Sanitized HTML (annotation profile, §3.2). |
| `highlighted_text` | text, nullable | Roots only. Plain text, ≤ 500 chars. |
| `prefix` | string(255), nullable | Roots only. ≤ 5 words, may be empty. |
| `suffix` | string(255), nullable | Roots only. ≤ 5 words, may be empty. |
| `is_processed` | boolean, default false | Roots only. |
| `processed_at` | timestamp, nullable | Set on mark, cleared on unmark. |
| `created_at`, `updated_at`, `deleted_at` | timestamps, soft delete | |

Indexes: `(comment_id, parent_annotation_id, deleted_at)` (the only read path);
`(author_id)` (user-lifecycle cascades).

No anchor offsets and no entity type/id are stored: the entity comes from the
root comment's `commentable_type`/`commentable_id`, and anchors are re-located
client-side when vNext displays them. The DB does not enforce "roots have a
highlight, replies don't" — the form request does.

### 2.2 Model

`Comment\Private\Models\CommentAnnotation` — `SoftDeletes`, `#[Table('comment_annotations')]`,
`#[Fillable([...])]`, casts `is_processed` → bool, `processed_at` → datetime.
Relations `comment()`, `parent()`, `replies()`; scopes `roots()`, `replies()`.

### 2.3 Lifecycle rules

| Trigger | Effect on annotations | Where |
|---------|----------------------|-------|
| Moderator deletes the root comment | Soft-delete every row with that `comment_id` | Inside `CommentService::deleteByModeration`, same transaction |
| Moderator empties the root comment | **Soft-delete them too** (decision #4) | Inside `CommentService::emptyContentByModeration`, same transaction |
| Moderator deletes one annotation | Soft-delete it and its replies | `AnnotationService` |
| User deleted | Nullify `author_id` (mirrors comments, not Quote's hard delete — an annotation is part of a comment thread the author still reads) | Existing `RemoveAuthorOnUserDeleted` listener path |
| User deactivated / reactivated | Soft-delete / restore their rows | Existing `SoftDeleteCommentsOnUserDeactivated` / `RestoreCommentsOnUserReactivated` paths |
| Chapter deleted | `CommentMaintenancePublicApi::deleteFor` **force-deletes** the comments; annotations follow through the FK | `comment_id` FK `cascadeOnDelete` |

The comment cascades are direct calls inside Comment rather than a listener on
Comment's own `CommentDeletedByModeration` event: same domain, same
transaction, no event round-trip. The user-lifecycle cases extend the existing
Comment listeners rather than adding three new ones.

## 3. PHP architecture

### 3.1 Public API — `Comment\Public\Api\AnnotationPublicApi` (v1 surface)

| Method | Purpose |
|--------|---------|
| `getForComment(int $commentId, int $viewerId): AnnotationListDto` | The commenter's annotations under one root comment, filtered to what the viewer may see, with per-row action flags. Feeds the server-mode pop-up. |
| `setProcessed(int $annotationId, int $byUserId, bool $value): void` | Author/co-author toggle. |
| `moderatorDelete(int $annotationId, int $byUserId): void` | Soft delete + replies. |

Creation is **not** on the public API: it happens inside Comment, in the same
transaction as the root comment (§3.5), so no other domain needs it.

`CommentDto` gains `annotationCount: int` — the number of visible root
annotations under that root comment for the current viewer (0 for viewers who
may not see them). Computed in one grouped `COUNT` per comment-list page.

DTOs (`Comment/Public/Api/Contracts/`):

- `AnnotationDto` — `id, commentId, parentAnnotationId, authorId, authorProfile,
  body, highlightedText, prefix, suffix, isProcessed (null unless the viewer is
  an author), createdAt, replies: AnnotationDto[], canMarkAsProcessed,
  canDelete`. Replies share the shape (empty in v1).
- `AnnotationListDto` — `commentId, items: AnnotationDto[] (roots), viewerRole:
  'commenter'|'author'|'moderator'`.
- `AnnotationToCreateDto` — `body, highlightedText, prefix, suffix`.

No events emitted in v1 (spec §10). vNext adds `applyChanges` (post-publish
batch, **PUT**), `addReply`, and an entity-wide `getForEntity` for the gutter.

### 3.2 Services

- `AnnotationService` — create-with-comment, set processed, moderator delete,
  cascades called by `CommentService`. Wraps multi-row writes in transactions.
- `AnnotationAccessService` — visibility + per-row flags:
  - viewer = the root comment's author → their own annotations, no processed flag;
  - viewer is an author/co-author of the entity (per policy) or a moderator →
    all, with processed flag;
  - anyone else → nothing (the endpoint answers 403; the count is 0).
- `CommentBodySanitizer` gains a profile argument: `strict` (today, default) and
  `annotation` (`<strong>`, `<em>`, custom-emoji spans, paragraphs/line breaks only).

### 3.3 Policy / authorization

New methods on the `CommentPolicy` contract, defaulted in `DefaultCommentPolicy`
and passed through by `CommentPolicyRegistry` (existing pattern):

```php
public function canAnnotate(int $entityId, int $userId): bool;          // default false
public function canMarkAsProcessed(int $entityId, int $userId): bool;   // default false
public function getAnnotationBodyMaxLength(): ?int;                     // default 1000
public function getAnnotationHighlightMaxLength(): ?int;                // default 500
```

`ChapterCommentPolicy`: `canAnnotate` = `canCreateRoot` in v1 (signed in, not
an author of the story, no root comment yet); `canMarkAsProcessed` = author or
co-author of the story — the same author check `canCreateRoot` already uses, so
whoever cannot comment is exactly whoever can mark as processed. (Quote's
decisions #19/#20: beta readers are collaborators, not authors; BUILD verifies
the check excludes them, as `getAuthorIds()` does.) The old `isAnnotable()` / min-length methods are dropped: `canAnnotate`
defaulting to false already means "not annotable", and the min length is the
fixed "≥ 1 non-blank char" rule.

Enforcement points: the form request validates shape and lengths; the service
re-checks the policy (`canAnnotate` at create, `canMarkAsProcessed` at toggle);
moderator routes sit behind the existing `role:moderator,admin,tech-admin`
group. The Blade side only hides buttons — never the only gate.

### 3.4 Events and listeners

None added. Cascades are direct calls (§2.3); the user-lifecycle listeners
already in Comment gain the annotation step. `CommentPosted` still fires once
per root comment, so credits and `ChapterRootCommentNotification` are unchanged
whatever the annotation count.

### 3.5 Routes, controllers, form requests

All under the existing `['web','auth','compliant']` `comments` group in
`Comment/Private/routes.php`. The existing `POST /comments` has **no**
`role:user-confirmed` middleware — authorization is the policy's job, and
annotations follow the same rule.

| Method | Path | Gate | Purpose |
|--------|------|------|---------|
| `POST` | `/comments` (existing) | policy | Accepts an optional `annotations` field. Root comment + annotations in **one transaction**; any annotation failure rejects the whole post with a validation error. |
| `GET` | `/comments/{commentId}/annotations` | access service | `AnnotationListDto` as JSON for the pop-up. |
| `PUT` | `/comments/annotations/{annotationId}/processed` | policy `canMarkAsProcessed` | Body `{ value: bool }`. |
| `DELETE` | `/comments/annotations/{annotationId}` | moderator group | Moderator delete. |

`POST /comments` stays a classic form post that redirects: the drafts are
serialised by the client into a single hidden `annotations` input (JSON
string), decoded and validated by the existing `StoreCommentRequest` (each
item: body 1–1000 plain chars, `highlighted_text` 1–500, prefix/suffix ≤ 255;
only allowed on a root comment). Validation errors come back through the normal
`back()->withErrors` path. No `PATCH` anywhere — the production WAF blocks it.

Controllers: `AnnotationController` (index, processed) and
`AnnotationModerationController` (delete); `CommentController::store` delegates
annotation creation to the service.

## 4. Frontend architecture

All annotation JS lives in `Comment/Resources/js/annotations/`, one Vite entry
pushed `@once` by the Comment components that need it.

- **Annotate button** — `<x-comment::annotate-button>` (anonymous component),
  placed by Story in the `toolbar-actions` slot. Carries
  `data-requires-selection-within=".ce-block--text"`, so the toolbar hides it
  exactly when it hides « Citer » (decision #2). Rendered only when the viewer
  `canAnnotate`.
- **Capture form** — Alpine component teleported to `body`, placed under the
  selection like Quote's mini-form. Reads `getSelection()`, refuses a
  cross-block selection with the same `closestBlock` guard and the same inline
  error wording as Quote (decision #1), then
  `buildCanonicalText(article, { within: '.ce-block--text' })` →
  `extractAnchor`. Body via `<x-editor::rich-text toolbar="inline">`; Save
  (button or Ctrl/Cmd+Enter) / Cancel. The root is the chapter
  `article[data-quote-article]` — the same canonical text Quote uses, so both
  features anchor identically. (The attribute name is Quote's; renaming it to
  something neutral is not v1 work — see §9.)
- **Drafts** — stored in the **comment-draft `annotations` slot**, each
  `{ tempId, body, highlighted, prefix, suffix }`. One key per user × chapter
  shared with the root-comment draft, so the existing consumed-marker clears
  root and annotations together after a successful post. The draft module gains
  annotation add/edit/delete functions; no second storage key, no second schema.
- **Banner + drafts-mode pop-up** — inside `<x-comment::comment-list>`, above
  the root-comment form; visible when the slot is non-empty. Submitting the form
  fills the hidden `annotations` input from the slot.
- **« N annotations » button + server-mode pop-up** — in `comment-item`, between
  header and body, when `annotationCount > 0`. One pop-up instance per list,
  built on `<x-shared::modal>`, fetching `GET /comments/{id}/annotations` on
  first open and caching it until a mutation. Rows: highlighted text as a plain
  blockquote, body HTML, viewer-specific actions. The comment list loads through
  `GET /comments/fragments`, so the button and its count must render in the
  fragment path too, and the bundle must already be on the page (same constraint
  that made `comment-list` include the editor assets itself).

No page-load fetch of annotations: nothing is displayed in-chapter in v1, so the
first version's bootstrap `GET` is dropped.

## 5. Deptrac

**No new edge.** Story → CommentPublic already exists (policy registration,
comment list). Comment → Editor is Blade-only (as today for comment composers).
Comment → Shared (anchoring JS, modal) is allowed for every domain.

## 6. Testing strategy

- **Pest feature tests (default):** post root comment + annotations atomically;
  an invalid annotation rejects the whole post (no comment row); annotations
  refused on a reply and when `canAnnotate` is false (author, user who already
  commented); pop-up endpoint visibility per role (commenter, author, co-author,
  beta reader, other reader, moderator, guest); processed toggle (author ok,
  others 403; flag hidden from the commenter); moderator delete; cascades —
  delete / empty root comment, user deleted / deactivated / reactivated,
  chapter deleted; `annotationCount` on the comment list and fragments; Story's
  chapter page renders « Annoter » only for viewers who can annotate.
- **Unit:** sanitizer `annotation` profile; `ToolbarPresets` `inline`.
- **Vitest:** draft-slot add/edit/delete/clear and consumed-marker clearing;
  capture form (cross-block refusal, length errors, extracted anchor); pop-up
  rendering per `viewerRole`; hidden-input serialisation on submit; annotate
  button hidden by `data-requires-selection-within` across an image caption.
- **VERIFY (browser):** selection → « Citer » + « Annoter » side by side; touch
  selection on mobile; full loop for reader → author → moderator; Quote tints
  and heat unaffected.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Schema for annotations + replies | single table with `parent_annotation_id` / two tables | Single table, `comment_id` denormalised on replies | One-query fetch, one model. (Rev. 1, kept.) |
| 2 | `processed_at` | keep / drop | Keep | Cheap, useful later. (Rev. 1, kept.) |
| 3 | Count on the comment list | eager grouped COUNT / lazy | Eager, one grouped COUNT per page | The button must render with its number. (Rev. 1, kept; field renamed `annotationCount`.) |
| 4 | v1 scope | full spec / core loop | Core loop, no in-chapter UI (§1.2) | (Rev. 1, kept.) |
| 5 | v1 trigger for the drafts pop-up | banner above the root form / tab strip (spec §4.3) | Banner with « Voir les annotations » | (Rev. 1, kept.) |
| 6 | Cross-block selections | refuse like Quote / allow (spec §4.1) | **Refuse** | DECISIONS #1. One rule for both features; vNext tint reuses Quote's wrapping without the boundary seam. Multi-paragraph inside one block stays allowed. |
| 7 | Annotatable areas | text blocks / + captions / + whole images | **Text blocks only** (`.ce-block--text`) | DECISIONS #2. Same selector as Quote; image annotation stays vNext (no stable block id exists to anchor it). |
| 8 | Per-annotation Report | v1 with `chapter-annotation` topic / defer | **Defer** | DECISIONS #3. Resolves the spec/arch contradiction; reports target the root comment. Supersedes rev. 1 decision #10. |
| 9 | Emptying the root comment | delete annotations / keep | **Delete** | DECISIONS #4. Matches spec §9; supersedes rev. 1 §2.3. |
| 10 | Draft storage | new `chapter-annotations:*` keys / comment-draft `annotations` slot | Comment-draft slot | The slot is reserved for this; one key means the existing consumed marker clears everything on post. |
| 11 | Annotation editor | inline token array / new Editor preset | Preset `inline` | Editor's rule; supersedes rev. 1 §7 (`<x-shared::editor>` no longer exists). |
| 12 | Comment cascades | listener on Comment's own event / direct call | Direct call in `CommentService` | Same domain, same transaction. |
| 13 | Data for the pop-up | page-load fetch of the whole chapter / per-comment fetch on open | Per-comment on open | Nothing in-chapter needs it in v1. |
| 14 | Create endpoint | JSON API / existing form post + hidden field | Existing form post | Keeps redirect, flash, consumed marker and error display as they are. |

## 8. File layout (new files)

```
app/Domains/Comment/
├── Database/Migrations/YYYY_MM_DD_HHiiss_create_comment_annotations_table.php
├── Public/Api/
│   ├── AnnotationPublicApi.php
│   └── Contracts/{AnnotationDto,AnnotationListDto,AnnotationToCreateDto}.php
├── Private/
│   ├── Controllers/{AnnotationController,AnnotationModerationController}.php
│   ├── Models/CommentAnnotation.php
│   ├── Requests/SetAnnotationProcessedRequest.php
│   ├── Services/{AnnotationService,AnnotationAccessService}.php
│   └── Resources/
│       ├── lang/fr/annotations.php
│       └── views/components/
│           ├── annotate-button.blade.php
│           └── partials/{annotation-form,annotation-banner,annotation-modal}.blade.php
└── Resources/js/annotations/   (capture form, banner, modal, api client + *.test.js)
```

## 9. Risks acknowledged

- **Three highlighters on one article (vNext).** Quote's reader tint and author
  heat each strip their marks, `normalize()` and re-wrap; an annotation tint
  would be a third. Before vNext adds in-chapter display, coordinate the
  renderers (or move to the CSS Custom Highlight API) and share Quote's
  right-margin gutter rather than adding a second one. Not a v1 concern — v1
  adds no mark.
- **`data-quote-article` as a shared root.** Annotations anchor on a
  Quote-named attribute. Fine while both live on the chapter page; rename to a
  neutral attribute if a second annotable entity appears.
- **Toolbar gate is the union of two permissions** computed in Story's view.
  If a third action joins, move the union into a small view-model helper.
- **Drafts can outlive their passage.** A draft captured before the author edits
  the chapter is posted with its stale anchor; harmless in v1 (nothing is
  re-anchored), surfaces as `missing` once vNext displays them.
- **Selection gesture** remains untested by Vitest (happy-dom); covered in VERIFY.

## 10. Post-v1 roadmap (not committed)

| Letter | Theme | Notes |
|--------|-------|-------|
| A | Quick-emoji reactions | Three more toolbar buttons; no schema change. |
| B | Post-publish add / edit / delete | Pending-changes slot, save banner, `PUT /comments/{id}/annotations`, `applyChanges`. `canAnnotate` stops requiring "no root comment yet". |
| C | Replies | `addReply`; schema ready. |
| D | In-chapter display | Re-anchoring via `findAnchor`, tint + gutter shared with Quote (§9), popover with processed toggle. |
| E | Filter menu | Commenter checklist + show processed, right margin only. |
| F | Per-annotation Report | `chapter-annotation` moderation topic + formatter + seeded reasons. |
| G | Moderator « Vider le contenu » | On a single annotation. |
| H | Image annotation | Needs a stable block anchor; `buildCanonicalText` `within` is the text-side hook. |
