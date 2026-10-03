# Chapter annotations (v1) — implementation plan

> PLAN output, generated 2026-10-03 from `02-architecture.md` **revision 2**
> and the v1-only `01-functional.md`. The phase index at the top is the summary;
> everything below is detail. BUILD reads **one phase at a time** and nothing
> else of this file, so every phase stands alone: it names the
> `02-architecture.md` sections it needs and states what earlier phases left
> behind.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — #1–#4 and assumptions A1–A5 are
  settled; do not reopen them.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Schema and model — `comment_annotations`, `CommentAnnotation` | S | — | TODO |
| 2 | Policy contract — annotation methods on `CommentPolicy` (Default, Registry, News, Chapter) | S | — | TODO |
| 3 | Shared infrastructure — sanitizer `annotation` profile + Editor `inline` preset | S | — | TODO |
| 4 | Create path — `POST /comments` accepts `annotations`, one transaction | M | 1, 2, 3 | TODO |
| 4v | Checkpoint — comment posting on chapters and news | S | 4 | TODO |
| 5 | Read path — `AnnotationAccessService`, `AnnotationPublicApi::getForComment`, `GET /comments/{id}/annotations` | M | 4 | TODO |
| 6 | Author and moderator actions — `PUT …/processed`, `DELETE /comments/annotations/{id}` | S | 5 | TODO |
| 7 | `annotationCount` on `CommentDto` (page render + fragments) | S | 5 | TODO |
| 8 | Lifecycle — moderation cascades, user deleted / deactivated / reactivated, chapter deleted | S | 5, 7 | TODO |
| 9 | Shared infrastructure — comment-draft `annotations` slot API + consumed marker clears it | S | — | TODO |
| 9v | Checkpoint — root and reply comment drafts on chapters and news | S | 9 | TODO |
| 10 | Capture — « Annoter » button, capture form, chapter-page wiring | M | 2, 3, 9 | TODO |
| 10v | Checkpoint — Quote on the chapter page (toolbar, mini-form, highlights, heat) | S | 10 | TODO |
| 11 | Drafts banner, drafts-mode pop-up, publish with the root comment | M | 4, 10 | TODO |
| 12 | « N annotations » button and server-mode pop-up | M | 6, 7, 11 | TODO |
| 12v | Checkpoint — comment lists on chapters and news | S | 12 | TODO |
| 13 | End-to-end spec and domain documentation sweep | S | 12 | TODO |

17 rows: 13 phases + 4 checkpoints.

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/17)` resume correctly.
Checkpoint rows are run by `visual-verifier` and change no code.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Project rules that bite here: no HTTP `PATCH` (use `PUT`); code only under
  `app/Domains/<domain>` except the two shared config files this plan names
  explicitly (`config/purifier.php`, `vite.config.js`); no Python/sed to edit
  files.

## Existing infrastructure (not phases — verified against the code 2026-10-03)

| Brick | Where | Verified |
|-------|-------|----------|
| `<x-comment::annotable>` | `app/Domains/Comment/Private/Resources/views/components/annotable.blade.php` | Props `entityType`, `entityId`, `canAnnotate`, `viewerRole`, `maxSelection=500`; slot `toolbar-actions` is rendered **inside** `<template id="comment-toolbar-template">` and cloned by the toolbar on each selection — so a toolbar button must not carry the form markup itself. |
| Selection toolbar | `app/Domains/Comment/Resources/js/annotable/toolbar.js` | Honours `data-requires-selection-within`, `data-can-annotate`, `data-max-selection`; calls `Alpine.initTree` on the clone. |
| Anchoring | `app/Domains/Shared/Resources/js/anchoring/` | `buildCanonicalText(root, { within })`, `extractAnchor(range, root, canonical)`, `closestBlock` (`block-elements.js`), `trimRangeToText` (`text-range.js`). Quote's `Quote/Resources/js/quote/ui/mini-form.js` is the reference consumer (cross-block guard included). |
| Comment drafts | `app/Domains/Comment/Resources/js/comment-draft/index.js` | Key `comment-drafts:{userId}:{entityType}:{entityId}`, state `{ version: 1, root, reply, annotations: [] }`. **Gap:** `annotations` is persisted but there is no API for it, and the consumed marker (`scope: 'root'`) only calls `clearRoot` — annotations are **not** cleared today. Phase 9 closes this. |
| Editor | `app/Domains/Editor/…/rich-text.blade.php`, `Editor/Private/Support/ToolbarPresets.php` | Presets `default`, `links`, `editorial`, `narrative`; no `inline` yet (phase 3). `window.initQuillEditor(id)` is idempotent; writing the hidden `#quill-editor-area-{id}` textarea and dispatching `input` pushes content into Quill. |
| Chapter page | `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` ~l.200–235 | `<x-comment::annotable :can-annotate="$canQuoteStory">` with `<x-quote::toolbar-button>` in the slot and `<x-quote::mini-form />` beside it; comments render via `<x-comment::comment-list-component … page="0">` (lazy: every comment item on a chapter comes from `GET /comments/fragments`). |
| `<x-shared::modal>` | `app/Domains/Shared/Resources/views/components/modal.blade.php` | Breeze-style modal (`name`, `show`, `maxWidth`). |

---

## Phase 1 — Schema and model

**Goal.** Create the `comment_annotations` table and its Eloquent model, with
nothing reading or writing it yet.

Architecture: §2.1, §2.2.

**Deliverables.**
- `app/Domains/Comment/Database/Migrations/2026_10_03_000001_create_comment_annotations_table.php`
  — columns exactly as §2.1 (`comment_id` FK `comments.id` `cascadeOnDelete`;
  `parent_annotation_id` nullable self-FK `cascadeOnDelete`; `author_id`
  nullable, no FK; `body` text; `highlighted_text` text nullable; `prefix`,
  `suffix` string(255) nullable; `is_processed` bool default false;
  `processed_at` nullable; timestamps; `softDeletes`). Indexes
  `(comment_id, parent_annotation_id, deleted_at)` and `(author_id)`. `down()`
  drops the table.
- `app/Domains/Comment/Private/Models/CommentAnnotation.php` — `SoftDeletes`,
  `#[Table('comment_annotations')]`, `#[Fillable([...])]` (all writable columns
  above), `protected $casts` (`is_processed` → bool, `processed_at` → datetime,
  integer ids). Relations `comment()` (belongsTo `Comment`), `parent()`,
  `replies()` (hasMany self on `parent_annotation_id`); local scopes
  `scopeRoots()` (`whereNull('parent_annotation_id')`) and `scopeRepliesOnly()`
  (`whereNotNull(...)` — named so it does not clash with the `replies()`
  relation).

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/CommentAnnotationModelTest.php`
  - `it persists a root annotation under a comment and reads it back with casts`
  - `it soft-deletes and restores an annotation`
  - `it removes annotations when their comment is force-deleted` (FK cascade —
    proves the chapter-deleted and moderator-delete paths of phase 8 need no
    extra code; SQLite enforces FKs here, `config/database.php`
    `foreign_key_constraints` defaults to true)
  - `it scopes roots and replies apart`

**Acceptance.**
- ✅ `php artisan migrate:fresh` and a rollback of the new migration both run.
- ✅ Force-deleting a comment leaves no `comment_annotations` row for it, trashed or not.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Policy contract

**Goal.** Add the annotation questions to Comment's per-entity policy contract
and answer them for chapters, with no caller yet.

Architecture: §3.3, §1.3 (Story bullet).

**Deliverables.**
- `app/Domains/Comment/Public/Api/Contracts/CommentPolicy.php` — four methods:
  `canAnnotate(int $entityId, int $userId): bool`,
  `canMarkAsProcessed(int $entityId, int $userId): bool`,
  `getAnnotationBodyMaxLength(): ?int`, `getAnnotationHighlightMaxLength(): ?int`.
- `.../Contracts/DefaultCommentPolicy.php` — defaults `false`, `false`, `1000`, `500`.
- `app/Domains/Comment/Public/Api/CommentPolicyRegistry.php` — four
  pass-through methods taking `string $entityType` first (existing pattern).
- `app/Domains/News/Private/Services/NewsCommentPolicy.php` — implements the
  interface directly (not via `DefaultCommentPolicy`), so it **must** gain the
  four methods, returning the defaults (`false`, `false`, `1000`, `500`).
  Architecture §1.3 omits this; see Open items #1.
- `app/Domains/Story/Private/Services/ChapterCommentPolicy.php` —
  `canAnnotate` = `$userId > 0 && $this->canCreateRoot($entityId, $userId)`
  (guard `0`: `canCreateRoot` alone returns true for a guest id);
  `canMarkAsProcessed` = `$this->chapters->isUserAuthorOfChapter($entityId, $userId)`
  (that check uses `Story::authors()`, i.e. collaborators with role `author`
  only — beta readers excluded, verified); lengths 1000 / 500.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/AnnotationPolicyRegistryTest.php`
  - `it answers the defaults for an entity type without a policy`
  - `it delegates the four annotation methods to a registered policy`
- `app/Domains/Story/Tests/Feature/Chapters/ChapterCommentPolicyIntegrationTest.php` (extend)
  - `canAnnotate: true for a reader who has not commented yet`
  - `canAnnotate: false for the author, a co-author, a reader who already posted a root comment, and user id 0`
  - `canMarkAsProcessed: true for the author and a co-author; false for a beta reader and for a plain reader`
- `app/Domains/News/Tests/Feature/NewsCommentPolicyTest.php` (extend)
  - `it refuses annotations on news`

**Acceptance.**
- ✅ Every existing `CommentPolicy` implementation compiles (News, Chapter, test doubles extending `DefaultCommentPolicy`).
- ✅ A beta reader of a story gets `canMarkAsProcessed === false` on its chapters.
- ✅ `pnpm run gate` green (deptrac: no new edge — Story and News already depend on CommentPublic).

---

## Phase 3 — Shared infrastructure: sanitizer profile and Editor preset

**Goal.** Give Comment a sanitizer profile for annotation bodies and Editor an
`inline` toolbar preset, both additive (existing defaults untouched).

Architecture: §3.2 (sanitizer bullet), §1.3 (Editor bullet), DECISIONS A2.

**Deliverables.**
- `config/purifier.php` — new profile `annotation`: elements
  `p,br,strong,em,span`; attribute `span.class`; allowed classes = the
  `ql-custom-emoji*` list already used by `strict`; `AutoFormat.AutoParagraph`
  true; same hardening flags as `strict`. (Config lives outside `app/Domains`
  for every profile; this is the existing convention, not a new exception.)
- `app/Domains/Comment/Private/Support/CommentBodySanitizer.php` —
  `sanitizeToHtml(string $body, string $profile = self::STRICT)` and
  `plainTextLength(string $body, string $profile = self::STRICT)`; public
  constants `STRICT = 'strict'`, `ANNOTATION = 'annotation'`. Existing callers
  pass nothing and keep `strict`.
- `app/Domains/Editor/Private/Support/ToolbarPresets.php` — preset
  `'inline' => ['bold', 'italic', 'custom-emoji']`.
- `app/Domains/Editor/README.md` — list the `inline` preset where presets are documented.

**Tests.**
- `app/Domains/Comment/Tests/Unit/CommentBodySanitizerTest.php` (new)
  - `strict profile is the default and still keeps lists and blockquotes`
  - `annotation profile keeps strong, em, paragraphs, line breaks and custom-emoji spans`
  - `annotation profile strips lists, blockquotes, links, underline, alignment classes and scripts`
  - `plainTextLength counts plain characters under the annotation profile`
- `app/Domains/Editor/Tests/Feature/EditorToolbarPresetTest.php` (extend)
  - `it resolves the inline preset to bold, italic and custom-emoji`
  - `rich-text with toolbar="inline" renders data-toolbar with exactly those tokens`

**Acceptance.**
- ✅ All existing Comment tests pass unchanged (default profile untouched).
- ✅ `<x-editor::rich-text toolbar="inline">` renders a three-token toolbar.
- ✅ `pnpm run gate` green.

---

## Phase 4 — Create path: root comment + annotations in one transaction

**Goal.** `POST /comments` accepts an optional `annotations` field and stores the
root comment and all its annotations atomically, still firing exactly one
`CommentPosted`.

Architecture: §3.1 (`AnnotationToCreateDto`, "creation is not on the public
API"), §3.2 (`AnnotationService`), §3.3 (enforcement points), §3.4, §3.5
(`POST /comments` row and the paragraph under the table). Phases 1–3 left the
table/model, the policy methods (`canAnnotate`, max lengths via
`CommentPolicyRegistry`) and the `annotation` sanitizer profile.

**Deliverables.**
- `app/Domains/Comment/Public/Api/Contracts/AnnotationToCreateDto.php` —
  `body`, `highlightedText`, `prefix` (`?string`), `suffix` (`?string`).
- `app/Domains/Comment/Public/Api/Contracts/CommentToCreateDto.php` — new last
  constructor parameter `array $annotations = []` (`AnnotationToCreateDto[]`);
  existing positional callers keep working.
- `app/Domains/Comment/Private/Requests/StoreCommentRequest.php` —
  `prepareForValidation` decodes the `annotations` input when it is a JSON
  string (empty / missing → `[]`; undecodable → leave as is so `array` fails);
  rules `annotations` `nullable|array`, `annotations.*.body`
  `required|string`, `annotations.*.highlighted_text` `required|string|max:500`,
  `annotations.*.prefix` / `.suffix` `nullable|string|max:255`.
- `app/Domains/Comment/Private/Controllers/CommentController.php` — `store`
  maps validated items to `AnnotationToCreateDto`s on the `CommentToCreateDto`.
  Redirect and `comment.draft_consumed` flash unchanged.
- `app/Domains/Comment/Public/Api/CommentPublicApi.php::create` — when
  `annotations` is non-empty, before anything is written: refuse on a reply
  (`ValidationException` key `annotations`); refuse when
  `canAnnotate(entityType, entityId, userId)` is false; per item, plain body
  length under the `annotation` profile must be `1..getAnnotationBodyMaxLength`
  (non-blank after trim) and `mb_strlen(highlightedText)` ≤
  `getAnnotationHighlightMaxLength`. Errors keyed `annotations` (one message,
  from the lang file below).
- `app/Domains/Comment/Private/Services/AnnotationService.php` (new) —
  `createForComment(int $commentId, int $authorId, array $items): void`
  (sanitizes body with the `annotation` profile, stores highlight/prefix/suffix
  verbatim, roots only).
- `app/Domains/Comment/Private/Services/CommentService.php::postComment` — new
  optional `array $annotations = []`; comment insert + `createForComment` run in
  one `DB::transaction`; `CommentPosted` is emitted **after** the transaction
  returns, as today (listeners keep running outside any transaction).
- `app/Domains/Comment/Private/Resources/lang/fr/annotations.php` (new) —
  `errors.not_allowed`, `errors.invalid` (generic « Une annotation est invalide… »).

**Tests.** All in `app/Domains/Comment/Tests/Feature/Annotations/` unless noted,
on a real chapter (`publicStory`, `createPublishedChapter`, `bob` as reader)
so `ChapterCommentPolicy` is exercised end to end.
- `PostCommentWithAnnotationsTest.php`
  - `it stores the root comment and every annotation, attached to it and authored by the poster`
  - `it stores a root comment without annotations exactly as before`
  - `it sanitizes annotation bodies with the annotation profile`
  - `it rejects the whole post when one annotation body is blank (no comment row, no annotation row)`
  - `it rejects a body over 1000 plain characters and a highlight over 500`
  - `it rejects annotations sent with a reply`
  - `it rejects annotations from the chapter author, a co-author, and a reader who already has a root comment`
  - `it keeps the old body and returns errors under the annotations key on rejection`
  - `it fires exactly one CommentPosted for a root comment with three annotations`
  - `it rejects an annotations input that is not valid JSON`
- `app/Domains/Story/Tests/Feature/Chapters/ChapterCommentPolicyIntegrationTest.php` (extend)
  - `posting a root comment with annotations grants one credit and sends one ChapterRootCommentNotification`

**Acceptance.**
- ✅ A rejected annotation leaves zero rows in `comments` and `comment_annotations`.
- ✅ A user who already posted a root comment gets a validation error on `annotations`, nothing stored.
- ✅ Credits and notifications identical with or without annotations.
- ✅ Comment README: `POST /comments` documents the `annotations` field.
- ✅ `pnpm run gate` green.

---

## Checkpoint 4v — comment posting on chapters and news

Phase 4 reshaped the root-comment create path (`CommentPublicApi::create`,
`CommentService::postComment`, `StoreCommentRequest`) used by every comment in
the app. Check that it still behaves as before, with no annotation involved.

- Chapter page, as a confirmed reader: post a root comment (≥ 140 chars) →
  redirect to `#comments`, comment visible, credit granted, author notified;
  post a reply; edit own comment.
- Chapter page, as the author: no root form; reply to a comment works.
- News article, as a confirmed user: post a root comment and a reply.
- Validation error path: too-short root comment keeps the typed text.
- e2e: `e2e/tests/core/chapter-comments.spec.ts`,
  `e2e/tests/core/comment-thread.spec.ts`,
  `e2e/tests/core/comment-draft-consume.spec.ts`.

Screenshots under `shots/checkpoint-4v/`.

---

## Phase 5 — Read path: access service, public API, `GET` endpoint

**Goal.** Serve one commenter's annotations under one root comment, filtered to
what the viewer may see, as JSON for the pop-up.

Architecture: §3.1 (`getForComment`, `AnnotationDto`, `AnnotationListDto`),
§3.2 (`AnnotationAccessService`), §3.5 (`GET` row). Phase 4 left
annotations stored under root comments; phase 2 left
`CommentPolicyRegistry::canMarkAsProcessed`.

**Deliverables.**
- `app/Domains/Comment/Private/Services/AnnotationAccessService.php` —
  resolves the viewer's relation to a root comment:
  `commenter` (viewer = comment author) → own annotations, `isProcessed = null`;
  else `author` (`canMarkAsProcessed(entityType, entityId, viewerId)`) → all,
  with `isProcessed`; else `moderator` (role `moderator|admin|tech-admin`,
  via `AuthPublicApi::getRolesByUserIds([$viewerId])`) → all, with
  `isProcessed`; else none. Per-row flags: `canMarkAsProcessed` only for
  `author`; `canDelete` only for `moderator`. Precedence is that order (a
  moderator who wrote the comment sees it read-only, as its commenter).
- `app/Domains/Comment/Public/Api/Contracts/AnnotationDto.php` and
  `AnnotationListDto.php` — shapes per §3.1, each with `toArray()` (snake_case
  keys: `id, comment_id, parent_annotation_id, author_id, author_profile, body,
  highlighted_text, prefix, suffix, is_processed, created_at, replies,
  can_mark_as_processed, can_delete`; list: `comment_id, viewer_role, items`).
- `app/Domains/Comment/Public/Api/AnnotationPublicApi.php` —
  `getForComment(int $commentId, int $viewerId): AnnotationListDto`; throws
  `ModelNotFoundException` for an unknown or trashed comment and
  `AuthorizationException` when the viewer may see nothing. Roots only, oldest
  first, `replies` empty in v1.
- `app/Domains/Comment/Private/Controllers/AnnotationController.php` — `index`.
- `app/Domains/Comment/Private/routes.php` — `GET /comments/{commentId}/annotations`
  (`whereNumber`), name `comments.annotations.index`, inside the existing
  `['web','auth','compliant']` group.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/GetAnnotationsEndpointTest.php`
  (real chapter; reader `bob` posts a root comment with two annotations)
  - `the commenter gets their annotations without the processed flag and no actions`
  - `the chapter author gets them with is_processed and can_mark_as_processed`
  - `a co-author gets the same as the author`
  - `a beta reader gets 403`
  - `another reader gets 403`
  - `a moderator gets them with can_delete and without can_mark_as_processed`
  - `a guest is redirected to login`
  - `an unknown comment id answers 404`
  - `soft-deleted annotations are not listed`
  - `highlighted_text is returned as stored (plain text)`

**Acceptance.**
- ✅ A reader who is neither the commenter, an author, nor a moderator gets 403 and no data.
- ✅ `is_processed` is `null` in the commenter's payload.
- ✅ `pnpm run gate` green; deptrac: no new edge (Comment → Auth already exists).

---

## Phase 6 — Author and moderator actions

**Goal.** Let chapter authors toggle "processed" and moderators delete one
annotation, each enforced server-side.

Architecture: §3.1 (`setProcessed`, `moderatorDelete`), §3.3, §3.5 (`PUT` and
`DELETE` rows), §2.3 row "Moderator deletes one annotation". Phase 5 left
`AnnotationAccessService` and `AnnotationPublicApi`.

**Deliverables.**
- `AnnotationPublicApi::setProcessed(int $annotationId, int $byUserId, bool $value): void`
  — loads the root annotation and its comment; refuses (`AuthorizationException`)
  unless `canMarkAsProcessed` for the comment's entity; refuses a reply row
  (422). Sets `is_processed` and `processed_at` (now / null).
- `AnnotationPublicApi::moderatorDelete(int $annotationId, int $byUserId): void`
  — soft-deletes the row and its replies in one transaction (via
  `AnnotationService`).
- `app/Domains/Comment/Private/Requests/SetAnnotationProcessedRequest.php` —
  `value` `required|boolean`.
- `AnnotationController::processed` — JSON `{ id, is_processed }`.
- `app/Domains/Comment/Private/Controllers/AnnotationModerationController.php` —
  `delete`, answers 204.
- `routes.php` — `PUT /comments/annotations/{annotationId}/processed`
  (`comments.annotations.processed`) in the auth group;
  `DELETE /comments/annotations/{annotationId}` (`comments.moderation.annotations.delete`)
  inside the existing `role:moderator,admin,tech-admin` sub-group. No `PATCH`.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/SetAnnotationProcessedTest.php`
  - `the chapter author marks and unmarks an annotation (is_processed, processed_at)`
  - `a co-author's mark is seen by the other author`
  - `the commenter gets 403 and the row is unchanged`
  - `a beta reader, another reader and a moderator who is not an author get 403`
  - `the commenter's GET payload still has is_processed null after marking`
  - `value is required and boolean (422)`
- `app/Domains/Comment/Tests/Feature/Annotations/ModeratorDeleteAnnotationTest.php`
  - `a moderator soft-deletes one annotation; the others under the comment stay`
  - `the deleted annotation disappears from every viewer's GET payload`
  - `a non-moderator (author, commenter) is redirected by the role middleware and the row is untouched`
    (the `role` middleware answers 302 to the dashboard, not 403)
  - `an unknown id answers 404`

**Acceptance.**
- ✅ Only an author/co-author of the chapter can change `is_processed`.
- ✅ Only moderator/admin/tech-admin can delete; the root comment is untouched.
- ✅ `pnpm run gate` green.

---

## Phase 7 — `annotationCount` on `CommentDto`

**Goal.** Every root comment DTO carries the number of annotations the current
viewer may see, computed with one grouped `COUNT` per page, on both the page
render and the fragments endpoint.

Architecture: §3.1 (paragraph on `CommentDto`), §7 row 3, §4 last bullet
(fragments). Phase 5 left `AnnotationAccessService`.

**Deliverables.**
- `app/Domains/Comment/Public/Api/Contracts/CommentDto.php` — new last
  constructor property `public int $annotationCount = 0`; `toArray()` adds
  `annotation_count`.
- `app/Domains/Comment/Private/Services/AnnotationService.php` —
  `countRootsByComment(array $commentIds): array<int,int>` (one grouped query,
  non-trashed roots).
- `AnnotationAccessService` — `visibleCounts(string $entityType, int $entityId,
  array $rootComments, int $viewerId): array<int,int>`: author/co-author or
  moderator → all counts; otherwise only the viewer's own comment keeps its
  count; guests (id 0) → all zero. One policy call and one role lookup per page.
- `app/Domains/Comment/Private/Mappers/CommentDtoMapper.php` /
  `CommentPublicApi::getFor` and `getCommentInternal` — attach the counts to
  root DTOs (replies stay 0).

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/AnnotationCountTest.php`
  - `the commenter sees the count on their own comment and 0 on others'`
  - `the chapter author and a moderator see every comment's count`
  - `another reader sees 0 everywhere`
  - `soft-deleted annotations are not counted`
  - `the count query runs once per page whatever the number of comments` (query log)
- `app/Domains/Comment/Tests/Feature/CommentFragmentControllerTest.php` (extend)
  - `fragments carry annotation_count through the DTO used by comment-item` — at
    this phase assert on the DTO via `CommentPublicApi::getFor`; the markup comes
    in phase 12.

**Acceptance.**
- ✅ No N+1: one extra query per comment page.
- ✅ Existing comment list and fragment tests unchanged and green.
- ✅ `pnpm run gate` green.

---

## Phase 8 — Lifecycle

**Goal.** Annotations follow their root comment and their author through
moderation, user deletion/deactivation/reactivation and chapter deletion.

Architecture: §2.3, DECISIONS #4, A3. Phases 1–7 left the table, the read
endpoint (phase 5) and `annotationCount` (phase 7). Open items #2 resolved by
DECISIONS #5: no row change on deactivate/reactivate.

**Deliverables.**
- `CommentService::emptyContentByModeration` — inside the existing transaction,
  `AnnotationService::softDeleteForComment($commentId)` (decision #4).
- `CommentService::deleteByModeration` — no new code: the comment is
  **force-deleted** (`CommentRepository::deleteWithChildren`), so the
  `comment_id` FK cascade removes its annotations (Open items #3).
- `app/Domains/Comment/Private/Listeners/RemoveAuthorOnUserDeleted.php` — also
  `AnnotationService::nullifyAuthor($userId)` (A3).
- Deactivation / reactivation — per the resolution of Open items #2. Planned
  default: **no row change**; annotations are only reachable through their
  root comment (phase 5 loads the comment with the default soft-delete scope,
  phase 7 counts only for listed comments), and in v1 the annotation author is
  always the root-comment author, so the comment's soft delete hides them and
  its restore brings them back — without resurrecting annotations a moderator
  deleted. If the user prefers the literal §2.3 wording, the listeners
  `SoftDeleteCommentsOnUserDeactivated` / `RestoreCommentsOnUserReactivated`
  gain `softDeleteByAuthor` / `restoreByAuthor` on annotations and the two
  "stays deleted" tests below must be dropped.
- Chapter deleted — no new code: `CommentMaintenancePublicApi::deleteFor`
  force-deletes the comments; FK cascade.

**Tests.** `app/Domains/Comment/Tests/Feature/Annotations/AnnotationLifecycleTest.php`
- `moderator delete of the root comment leaves no annotation row`
- `moderator empty-content soft-deletes the comment's annotations, the comment stays`
- `user deleted: annotations kept with author_id null, still listed to the chapter author`
- `user deactivated: GET on their comment answers 404 and the comment list no longer shows it`
- `user reactivated: annotations are listed again`
- `an annotation deleted by a moderator stays deleted after its author is deactivated then reactivated`
- `annotations of an emptied root comment stay deleted after deactivate/reactivate`
- `CommentMaintenancePublicApi::deleteFor removes the annotations`
- `app/Domains/Story/Tests/Feature/Chapters/DeleteChapterTest.php` (extend):
  `deleting a chapter removes the annotations under its comments`

**Acceptance.**
- ✅ Every row of §2.3 has a passing test.
- ✅ Existing `UserDeactivatedSoftDeleteCommentsTest`, `UserReactivatedRestoreCommentsTest`, `UserDeletedCleanupTest`, `CommentModeration*Test` unchanged and green.
- ✅ `pnpm run gate` green.

---

## Phase 9 — Shared infrastructure: comment-draft `annotations` slot

**Goal.** Give the comment-draft store an API for the reserved `annotations`
slot and make a successful root post clear it with the root draft.

Architecture: §4 "Drafts" bullet, §7 row 10, DECISIONS A1. Existing code:
`app/Domains/Comment/Resources/js/comment-draft/index.js` (see "Existing
infrastructure" above — the root consumed marker currently clears only `root`).

**Deliverables.**
- `comment-draft/index.js` — exported and on `window.commentDrafts`:
  `listAnnotations(userId, entityType, entityId)`,
  `addAnnotation(userId, entityType, entityId, { body, highlighted, prefix, suffix })`
  → returns the stored item with a generated `tempId`,
  `updateAnnotation(userId, entityType, entityId, tempId, body)`,
  `removeAnnotation(userId, entityType, entityId, tempId)`,
  `clearAnnotations(userId, entityType, entityId)`. Each mutation dispatches a
  `comment-drafts:annotations-changed` window event with
  `{ entityType, entityId, count }`. `load` keeps only well-formed items
  (`tempId`, string `body`, string `highlighted`). Schema version unchanged
  (the slot already exists in v1 of the schema).
- `applyConsumedMarker` — scope `root` clears `root` **and** `annotations`;
  scope `reply` unchanged. The inline flash script in
  `Comment/Private/Resources/views/components/comment-list.blade.php` mirrors
  it (`clearAnnotations` after `clearRoot`).
- `app/Domains/Comment/README.md` "Draft autosave" section — document the slot.

**Tests.** `app/Domains/Comment/Resources/js/comment-draft/index.test.js` (extend)
- `adds, lists, updates and removes annotation drafts per user and entity`
- `another user's key never sees the drafts`
- `annotation drafts survive a root-body save and a reply save`
- `a root consumed marker clears root and annotations; a reply marker clears neither`
- `the storage key is removed once root, reply and annotations are all empty`
- `malformed annotation items are dropped on load`
- `mutations dispatch comment-drafts:annotations-changed with the count`

**Acceptance.**
- ✅ Existing comment-draft tests pass unchanged.
- ✅ `pnpm run gate` green.

---

## Checkpoint 9v — root and reply comment drafts

Phase 9 changed the shared comment-draft store and the post-submit consume
path. Check that the existing draft behaviour survived.

- Chapter page, confirmed reader: type a root comment, reload → restored; post
  it → editor empty after redirect, nothing restored on next reload.
- Same with a reply draft (reply form auto-opens after reload; cleared after post).
- News article: same root-draft round trip.
- Two users on the same browser: user B never sees user A's draft.
- e2e: `e2e/tests/core/comment-draft-consume.spec.ts`,
  `e2e/tests/core/comment-thread.spec.ts`.

Screenshots under `shots/checkpoint-9v/`.

---

## Phase 10 — Capture: « Annoter » button, capture form, chapter wiring

**Goal.** A reader who may annotate selects text in one text block, clicks
« Annoter », types a note and saves it as a local draft.

Architecture: §1.2, §1.3 (Story bullet), §4 "Annotate button" and "Capture
form" bullets, §5, DECISIONS #1, #2, A2, A5. Phases left behind:
`ChapterCommentPolicy::canAnnotate` (phase 2), the `inline` Editor preset
(phase 3), `window.commentDrafts.addAnnotation` / `updateAnnotation` (phase 9).

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/annotate-button.blade.php`
  — anonymous component, `@props(['canAnnotate' => false])`; renders nothing
  when false. A `<button type="button"
  data-requires-selection-within=".ce-block--text">` that dispatches
  `annotation:open-form` (window). It lives inside the toolbar `<template>`, so
  it holds no form markup; it `@push('head-scripts')`es the bundle `@once`.
- `app/Domains/Comment/Private/Resources/views/components/annotation-form.blade.php`
  — anonymous component `@props(['entityType', 'entityId'])`, rendered by the
  consumer **outside** the annotable region, like `<x-quote::mini-form />`
  (deviation from §8's `partials/annotation-form`: the toolbar template cannot
  host it — see Open items #4). Contains `<x-editor::rich-text
  id="annotation-body-editor" name="annotation_body" toolbar="inline" :max="1000"
  isMandatory="true">`, the highlighted text as a plain quote (`x-text`),
  Enregistrer / Annuler, the inline error line, and the data attributes for the
  error wordings. `data-user-id`, `data-entity-type`, `data-entity-id`.
- `app/Domains/Comment/Resources/js/annotations/index.js` — Vite entry,
  registers `Alpine.data('annotationForm', …)` on `alpine:init` (same pattern
  as `Quote/Resources/js/quote/index.js`).
- `app/Domains/Comment/Resources/js/annotations/capture-form.js` —
  `annotationForm()`:
  - `openForm()` from the current selection: root `article[data-quote-article]`;
    `trimRangeToText` + `closestBlock` cross-block guard → inline error, Save
    disabled (wording = Quote's `highlight_multi_block`, copied into Comment's
    lang file); `buildCanonicalText(article, { within: '.ce-block--text' })` →
    `extractAnchor`; highlight > 500 → error, Save disabled; positions under
    the selection; clears the selection.
  - Calls `window.initQuillEditor('annotation-body-editor')` on first open
    (idempotent; the element may not exist at DOMContentLoaded if teleported),
    resets the editor by writing the hidden textarea and dispatching `input`.
  - `save()` (button or Ctrl/Cmd+Enter on the form; never on focus-out):
    refuses a blank body; `addAnnotation(...)` with `{ body, highlighted,
    prefix, suffix }`; closes. `cancel()` / Escape discards.
  - `openEdit({ tempId })` (used by phase 11): loads the draft's body, shows its
    highlight, centred, Save calls `updateAnnotation`.
- `vite.config.js` — add the entry `app/Domains/Comment/Resources/js/annotations/index.js`.
- `app/Domains/Comment/Private/Resources/lang/fr/annotations.php` — button label
  « Annoter », form title, Enregistrer / Annuler, errors (blank, too long,
  multi-block).
- `app/Domains/Story/Private/Controllers/ChapterController.php` (show) — compute
  `$canAnnotate = $userId !== null && $this->chapterCommentPolicy->canAnnotate($chapter->id, $userId)`
  (inject `ChapterCommentPolicy`, same domain) and pass it to the view.
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` —
  `:can-annotate="$canQuoteStory || $canAnnotate"`; in `toolbar-actions`, add
  `<x-comment::annotate-button :can-annotate="$canAnnotate" />` after the quote
  button; beside `<x-quote::mini-form />`, add
  `@if($canAnnotate)<x-comment::annotation-form entity-type="chapter" :entity-id="$vm->chapter->id" />@endif`.

**Tests.**
- `app/Domains/Story/Tests/Feature/Chapters/ChapterAnnotateButtonViewTest.php`
  - `a reader who has not commented sees « Annoter » with data-requires-selection-within=".ce-block--text"`
  - `a reader who already posted a root comment does not see « Annoter » (A5) but still sees « Citer »`
  - `the author and a co-author see neither the button nor the form`
  - `a guest sees no toolbar action and no form`
  - `a non-confirmed reader (role user) who may comment sees « Annoter » and gets data-can-annotate="true" without « Citer »`
  - `the annotation form renders the inline toolbar`
- `app/Domains/Comment/Resources/js/annotations/capture-form.test.js` (Vitest, happy-dom)
  - `extracts highlighted text, prefix and suffix from a selection inside one text block`
  - `refuses a selection spanning two .ce-block--text blocks with the multi-block error`
  - `refuses a highlight over 500 characters`
  - `refuses a blank body`
  - `save stores a draft through commentDrafts.addAnnotation and closes`
  - `Ctrl+Enter saves; blur does not`
  - `cancel stores nothing`
  - `openEdit pre-fills the body and save updates the same tempId`
- `app/Domains/Comment/Resources/js/annotable/toolbar.test.js` (extend)
  - `an annotate button is hidden like the quote button when the selection touches an image caption`

**Acceptance.**
- ✅ « Annoter » is rendered only when `canAnnotate` (server-side), never for the author, a co-author, a guest or a reader who already commented.
- ✅ Saving puts one item in `comment-drafts:{userId}:chapter:{chapterId}` → `annotations`.
- ✅ Quote's tests (`QuoteToolbarButtonViewTest`, Quote Vitest suites) unchanged and green.
- ✅ `pnpm run gate` green (including the asset build — new Vite entry).

---

## Checkpoint 10v — Quote on the chapter page

Phase 10 changed the chapter page's toolbar gate (`can-annotate` is now the
union of quote and annotate permissions) and added a second toolbar action.
Check that Quote is unaffected.

- Confirmed reader: select text → toolbar shows « Citer » and « Annoter » side
  by side; « Citer » → mini-form → save → highlight tint appears.
- Selection touching an image caption or a chapter-choice block (Advanced
  chapter): both buttons hidden.
- Selection over 500 chars: « Sélection trop longue ».
- Author: author heat / passage panel / summary still render; no toolbar.
- Reader who already commented: « Citer » only.
- Mobile width (375 px): touch selection shows the toolbar.
- No Quote e2e exists; visual only.

Screenshots under `shots/checkpoint-10v/`.

---

## Phase 11 — Drafts banner, drafts-mode pop-up, publish with the root comment

**Goal.** Above the root-comment form the reader sees their pending drafts,
reviews/edits/deletes them, and submitting the root comment publishes them.

Architecture: §4 "Banner + drafts-mode pop-up" and "Drafts" bullets, §3.5
(hidden `annotations` JSON input), spec §4.2–4.3. Phases left behind: the
create path accepting `annotations` (phase 4), the draft-slot API and the
`comment-drafts:annotations-changed` event (phase 9), the capture form with
`openEdit` (phase 10).

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/partials/annotation-banner.blade.php`
  — inside the root `<form data-comment-draft="root">` of `comment-list.blade.php`,
  above the editor: « {N} annotations, écrivez votre commentaire pour les
  sauvegarder » (`trans_choice`) + « Voir les annotations »; hidden while the
  slot is empty; `<input type="hidden" name="annotations">`; displays
  `$errors->first('annotations')` / any `annotations.*` error.
- `app/Domains/Comment/Private/Resources/views/components/partials/annotation-drafts-modal.blade.php`
  — `<x-shared::modal>` listing drafts: highlighted text as a plain blockquote
  (`x-text`), body (`x-html` of the locally-authored Quill HTML), **Modifier**
  (closes the modal, dispatches the form's `openEdit`), **Supprimer**
  (`removeAnnotation`).
- `comment-list.blade.php` — includes both partials in the root form; pushes
  the annotations bundle (`@once`, `head-scripts`) for authenticated viewers.
- `app/Domains/Comment/Resources/js/annotations/drafts.js` —
  `Alpine.data('annotationDrafts', …)`: reads the slot on init, listens to
  `comment-drafts:annotations-changed`; on the form's `submit`, serialises the
  slot into the hidden input as
  `[{ body, highlighted_text, prefix, suffix }]` (empty slot → empty input).
  Draft clearing on success is the consumed marker's job (phase 9); on
  failure the slot is untouched.
- `annotations.php` lang — banner (`trans_choice`), « Voir les annotations »,
  modal title, Modifier, Supprimer, empty state.

**Tests.**
- `app/Domains/Comment/Resources/js/annotations/drafts.test.js` (Vitest)
  - `the banner is hidden with no draft and shows the count with drafts`
  - `the count follows add / remove events`
  - `submit fills the hidden input with snake_case items`
  - `submit with no drafts leaves the input empty`
  - `Supprimer removes the draft and updates the count`
  - `Modifier opens the capture form in edit mode for that tempId`
- `app/Domains/Comment/Tests/Feature/Views/RenderCommentListComponentTest.php` (extend)
  - `the root form contains the hidden annotations input and the banner markup for a user who can comment`
  - `no banner when the viewer cannot create a root comment`
  - `an annotations validation error is displayed in the banner`
- `app/Domains/Comment/Tests/Feature/Annotations/PostCommentWithAnnotationsTest.php` (extend)
  - `the JSON produced by the banner (fixture string) is accepted end to end`

**Acceptance.**
- ✅ Reader flow (spec §11 steps 1–3) works in the browser: 3 drafts → banner « 3 annotations » → delete one, edit one → post → comment + 2 annotations stored, banner gone after redirect.
- ✅ A rejected post (root too short) keeps the drafts and the typed root body.
- ✅ `pnpm run gate` green.

---

## Phase 12 — « N annotations » button and server-mode pop-up

**Goal.** Under a published root comment, the commenter, chapter authors and
moderators open a pop-up listing that commenter's annotations with their
role's actions.

Architecture: §4 "« N annotations » button + server-mode pop-up" bullet, spec
§4.4. Phases left behind: `GET /comments/{id}/annotations` with `viewer_role`
and per-row `can_mark_as_processed` / `can_delete` (phase 5), `PUT …/processed`
and `DELETE /comments/annotations/{id}` (phase 6), `CommentDto::$annotationCount`
(phase 7), the bundle pushed by `comment-list` (phase 11).

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/partials/comment-item.blade.php`
  — between header and body, when `$comment->annotationCount > 0`, a button
  « {N} annotations » dispatching `annotations:open` with the comment id. Works
  in the fragments path (no `@push` from this partial).
- `app/Domains/Comment/Private/Resources/views/components/partials/annotation-modal.blade.php`
  — one `<x-shared::modal>` per comment list, included once by
  `comment-list.blade.php` (outside the `@if(canCreateRoot)` form, for
  authenticated viewers). Rows: highlighted text as plain blockquote
  (`x-text`), body (`x-html`, server-sanitized), processed marker (author view),
  actions per row flags.
- `app/Domains/Comment/Resources/js/annotations/api.js` — `fetchAnnotations`,
  `setProcessed` (PUT, JSON, CSRF header — same pattern as
  `Quote/Resources/js/quote/api/client.js`), `deleteAnnotation` (DELETE).
- `app/Domains/Comment/Resources/js/annotations/modal.js` —
  `Alpine.data('annotationsModal', …)`: fetch on first open per comment, cache
  until a mutation; toggle updates the row in place; delete removes the row and
  decrements the button's count; error line on failure.
- `annotations.php` lang — button (`trans_choice`), modal title, « Marquer comme
  traitée » / « Marquer comme non traitée », « Supprimer l'annotation »,
  processed marker, load error.

**Tests.**
- `app/Domains/Comment/Resources/js/annotations/modal.test.js` (Vitest)
  - `fetches once per comment and reuses the cache on reopen`
  - `commenter view: rows without actions and without processed marker`
  - `author view: toggle calls PUT and flips the row label`
  - `moderator view: delete calls DELETE, removes the row and decrements the count`
  - `renders highlighted text as text, never as HTML`
- `app/Domains/Comment/Tests/Feature/CommentFragmentControllerTest.php` (extend)
  - `the fragment shows « N annotations » to the commenter, the author and a moderator`
  - `the fragment shows no annotation button to another reader`
  - `no button when the comment has no annotation`
- `app/Domains/Comment/Tests/Feature/Views/RenderCommentListComponentTest.php` (extend)
  - `the annotations modal is rendered once and the bundle pushed for an authenticated viewer`

**Acceptance.**
- ✅ Spec §11 steps 4–5 work in the browser (author marks processed; moderator deletes one).
- ✅ The commenter never sees a processed marker.
- ✅ A reader who is none of the three roles sees no button (count 0 server-side) and gets 403 from the endpoint.
- ✅ `pnpm run gate` green.

---

## Checkpoint 12v — comment lists on chapters and news

Phase 12 changed `comment-item` (rendered on every comment, page and
fragments) and `comment-list`. Check that the existing list survived.

- Chapter with > 5 comments: infinite scroll loads more; reply, edit, share,
  report and moderator buttons still work on loaded items.
- Deep link `?comment=<id>#comments` highlights and scrolls.
- News article comments: list, reply, edit; no annotation button anywhere.
- Moderator: delete / empty-content on a root comment from the list.
- Mobile width: header row and the new button do not overflow.
- e2e: `e2e/tests/core/chapter-comments.spec.ts`,
  `e2e/tests/core/comment-thread.spec.ts`,
  `e2e/tests/core/comment-draft-consume.spec.ts`.

Screenshots under `shots/checkpoint-12v/`.

---

## Phase 13 — End-to-end spec and documentation sweep

**Goal.** Lock the full browser loop in Playwright and leave the domain docs
describing annotations without any planning link.

**Deliverables.**
- `e2e/tests/core/chapter-annotations.spec.ts` — reader selects in a text
  block → « Annoter » → saves 2 drafts → reload keeps them → banner → delete
  one → post the root comment → « 1 annotation » on the comment; author opens
  the pop-up and marks processed; moderator deletes it. Uses the existing
  `e2e/pages` / `e2e/support` helpers and seeders (extend
  `Comment/Database/Seeders/E2eCommentsSeeder.php` only if a fixture is missing).
- `app/Domains/Comment/README.md` — sections: annotations overview,
  `AnnotationPublicApi`, routes table (GET / PUT / DELETE), `comment_annotations`
  table, lifecycle rules, sanitizer profiles, front-end (annotate button,
  capture form, banner, modals).
- `app/Domains/Comment/AGENTS.md` — invariants: annotations only created with a
  root comment; no events; processed flag hidden from the commenter.
- `app/Domains/Story/README.md` — chapter page: `canAnnotate` and the toolbar union.
- `app/Domains/News/README.md` — only if it documents `NewsCommentPolicy`'s methods.

**Tests.**
- The new Playwright spec (run by `pnpm run e2e`; type-checked by the gate).

**Acceptance.**
- ✅ `pnpm run e2e` passes `chapter-annotations.spec.ts` and the existing comment specs.
- ✅ No domain `README.md` / `AGENTS.md` links to `docs/Feature_Planning`.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter, confirmed reader | Selection inside one text block → « Citer » + « Annoter » side by side | |
| Chapter, capture form | Form under the selection; bold / italic / emoji toolbar only; counter /1000; Save + Cancel; Ctrl/Cmd+Enter saves; clicking elsewhere does not save | |
| Chapter, cross-block selection (Advanced chapter) | Inline multi-block error, Save disabled | |
| Chapter, image caption / chapter-choice block | Selection touching them hides both buttons | |
| Chapter, selection > 500 chars | « Sélection trop longue », no action | |
| Chapter, reader with 0 drafts | No banner above the root form (empty state) | |
| Chapter, reader with 3 drafts | Banner « 3 annotations, écrivez votre commentaire pour les sauvegarder » + « Voir les annotations » | |
| Drafts pop-up | Each draft: plain quote + body + Modifier / Supprimer; edit round trip; deleting the last draft hides the banner | |
| Reload | Drafts and in-progress root comment survive a reload | |
| Second user, same browser | Sees none of the first user's drafts | |
| Publish | Root comment + annotations posted; banner gone; « N annotations » on the new comment | |
| Publish failure (root < 140 chars) | Error shown, drafts and typed root body kept | |
| Reader who already commented | No « Annoter » (A5); « Citer » still there; no banner | |
| Commenter's pop-up | Own annotations, read-only, no processed marker | |
| Author / co-author pop-up | All annotations; « Marquer comme traitée / non traitée » toggles; marker visible; shared between co-authors | |
| Beta reader / other reader | No « N annotations » button on anyone's comment | |
| Moderator pop-up | « Supprimer l'annotation » removes the row; count decrements | |
| Root comment emptied by moderation | Button gone (annotations deleted) | |
| Root comment deleted by moderation | Comment and annotations gone | |
| Deactivated commenter | Comment and button hidden; restored on reactivation, without resurrecting a moderator-deleted annotation | |
| Deleted commenter | Comment by « Esperluette disparue »; author still sees its annotations | |
| Guest | No toolbar, no banner, no button | |
| Mobile (375 px) | Touch selection → toolbar; capture form fits the viewport; pop-ups scroll | |
| Quote regression | Quote tints, panel, author heat unchanged | |
| News article | Comment list unchanged, no annotation UI | |

## Open items

1. **News must change too (phase 2).** `NewsCommentPolicy` implements
   `CommentPolicy` directly, so the four new interface methods break it unless
   News gains them. Architecture §1.3 lists only Story and Editor as touched
   domains. The plan adds four default-valued methods to `NewsCommentPolicy`;
   no behaviour change, no new deptrac edge.
2. **Deactivate/reactivate vs moderator soft-deletes (phase 8) — RESOLVED
   (DECISIONS #5): plan default kept, no annotation row change.** §2.3 says deactivation soft-deletes and
   reactivation restores the user's annotation rows. But moderator delete of
   one annotation and the decision-#4 cascade on an emptied root comment are
   *also* soft-deletes, and `restoreByAuthor` cannot tell them apart: a
   reactivated user would get moderator-removed annotations back. (Comments
   avoid this because moderator delete force-deletes them.) Plan default: touch
   no annotation row on deactivate/reactivate — the root comment's own soft
   delete already hides them, since in v1 an annotation's author is always its
   root comment's author. Alternative: follow §2.3 literally and accept the
   resurrection. Decision #4 is not reopened by either option.
3. **Moderator delete of a root comment (phase 8), information only.** §2.3
   says "soft-delete every row with that `comment_id`" inside
   `deleteByModeration`. The comment is force-deleted
   (`CommentRepository::deleteWithChildren`), so the FK cascade hard-deletes the
   annotations; an explicit soft delete beforehand would be dead code. The plan
   relies on the cascade.
4. **Capture form placement (phase 10), information only.** §8 places it at
   `components/partials/annotation-form.blade.php` and §4 says it is teleported
   to `body`. The toolbar slot is cloned from a `<template>`, so the button
   cannot carry the form, and the Comment components have no other hook on the
   chapter page. The plan makes it an anonymous component
   `<x-comment::annotation-form>` rendered by Story next to
   `<x-quote::mini-form />`, Quote's pattern. Quill is initialised on first open
   (`window.initQuillEditor` is idempotent) so teleporting stays possible.
5. **Bundle on news pages (phase 11), information only.** The annotations
   bundle is pushed by `comment-list` for every authenticated viewer, news
   included, because that component cannot know whether the entity type
   supports annotations without a new config field. It does nothing there.
   Gating it would mean adding `canAnnotate` to `CommentUiConfigDto`. Not
   planned.
6. **Existing `PATCH /comments/{commentId}` route**, information only: comment
   edit predates the no-PATCH rule (`Comment/Private/routes.php`). Out of scope;
   no new route in this plan uses PATCH.
