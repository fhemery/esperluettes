# Chapter annotations — v2 (writer side) — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) — never re-open a row there.
- v1 record (what already exists): [`_done/annotations.md`](../_done/annotations.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Refactor — extract `AnnotationItemValidator` from `CommentPublicApi` | S | — | DONE |
| 1v | Checkpoint — posting root comments (with and without annotation drafts) on chapters and news | S | 1 | DONE |
| 2 | v1 leftovers — policy highlight cap, counts respect `supportsAnnotations`, beta-reader count test | S | 1 | DONE |
| 3 | Story — lift A5 in `canAnnotate`, annotation mode + root id on the chapter page (toolbar still draft-only) | S | 2 | DONE |
| 4 | `saveChanges` — DTOs, service, public API, `PUT /comments/{id}/annotations` | M | 3 | DONE |
| 5 | Replies, read side — `getForComment` returns replies, `can_edit`/`can_reply`/`can_delete`, deactivated-writer filter | M | 4 | DONE |
| 6 | Replies, write side — `reply`, `deleteOwnReply`, their routes | S | 5 | DONE |
| 7 | JS infrastructure — `annotationChanges` slot in the comment-draft store (schema v2) + `api.js` calls | S | 4, 6 | DONE |
| 7v | Checkpoint — existing comment drafts and v1 annotation drafts survive the store bump | S | 7 | DONE |
| 8 | Toolbar — pending mode for the capture form + ❤️ 🔥 👍 reaction buttons | M | 7 | DONE |
| 9 | Save banner — `annotationChangesBanner` (count, Enregistrer, Tout annuler, 422 mapping) | M | 8 | DONE |
| 10 | Pop-up, commenter overlay — pending markers, undo, pending adds, Modifier / Supprimer | M | 9 | DONE |
| 11 | Pop-up, replies — thread, Répondre, delete own / moderator delete, author hint | M | 6, 10 | DONE |
| 12 | E2E — commenter ↔ author round trip; reuse or prune v1's orphan helpers | S | 11 | DONE |

14 rows (12 phases + 2 checkpoints).

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/14)` resume correctly.
Checkpoint rows are run by `visual-verifier` and change no code.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- No migration in this feature (architecture §1, §2.1). No `Route::patch`.
- No events, notifications, statistics or moderation topics (decisions #3, A4).
- French strings only, in `app/Domains/Comment/Private/Resources/lang/fr/annotations.php`.

Conventions used below: paths are relative to `app/Domains/Comment/` unless
they start with `app/`, `e2e/` or `docs/`. PHP feature tests use Pest helpers
already in the suite (`alice`, `bob`, `carol`, `addCollaborator`,
`createComment`, `generateDummyText`, `annotatedChapterCommentPayload`,
`annotationItem`) — reuse them; read an existing file under
`Tests/Feature/Annotations/` for the set-up pattern.

---

## Phase 1 — Refactor — extract `AnnotationItemValidator`

**Goal.** Move the per-item annotation validation out of
`CommentPublicApi::validateAnnotations` into a reusable private class, with no
behaviour change, so phase 4 can reuse it for `saveChanges`.

Architecture: §3.2 (bullet "Item validation is extracted").

**Today.** `Public/Api/CommentPublicApi.php::validateAnnotations()` (private)
checks: annotations only on a root, `canAnnotate`, then per item: plain body
length 1–`getAnnotationBodyMaxLength`, highlight ≤ `getAnnotationHighlightMaxLength`,
throwing `ValidationException` under the `annotations` key with
`comment::annotations.errors.invalid`.

**Deliverables.**
- New `Private/Support/AnnotationItemValidator.php`, constructor-injected with
  `CommentPolicyRegistry` and `CommentBodySanitizer`. Method:
  ```php
  /** @return string|null null when valid, else the translation key of the first failure */
  public function firstError(string $entityType, string $body, ?string $highlightedText, ?string $prefix, ?string $suffix): ?string
  ```
  Rules: plain body (sanitizer, `CommentBodySanitizer::ANNOTATION` profile)
  1–body max; when `$highlightedText !== null`, non-empty and `mb_strlen` ≤
  policy highlight max; prefix/suffix `mb_strlen` ≤ 255. Returns
  `comment::annotations.errors.invalid` for any failure (keep the single v1
  message; finer keys are not needed). `$highlightedText = null` means "body
  only" (used later for edits and replies).
- `Public/Api/CommentPublicApi.php`: `validateAnnotations()` keeps the
  root/`canAnnotate` check and calls the validator per item; same exception,
  same key, same message.
- No change to `StoreCommentRequest` yet (phase 2).

**Tests.**
- Existing ones, unchanged and green: `Tests/Feature/Annotations/PostCommentWithAnnotationsTest.php`,
  the rest of `Tests/Feature/Annotations/*`, `Tests/Unit/CommentBodySanitizerTest.php`.
- New unit test `Tests/Unit/AnnotationItemValidatorTest.php` (isolated logic,
  resolve it from the container with the real registry): blank body, body at
  max and max+1, highlight at max and max+1, `null` highlight skips the
  highlight rule, prefix of 256 chars refused.

**Acceptance.**
- ✅ `CommentPublicApi` no longer contains the per-item length logic; it calls `AnnotationItemValidator`.
- ✅ Every pre-existing test passes without edits.
- ✅ `pnpm run gate` green.

---

## Checkpoint 1v — posting root comments still works

Run by `visual-verifier`. Phase 1 reshaped the create-with-root path that every
comment post goes through.

- Chapter page, logged in as a confirmed reader with no comment yet: create two
  annotation drafts (« Annoter »), write a 140+ char root comment, post. The
  comment appears with « 2 annotations »; the pop-up lists both.
- Same chapter, a second reader: post a plain root comment (no drafts) — works.
- A reply to a root comment on the chapter — works.
- A news article: post a root comment — works, no annotation UI on the page.
- E2E: `pnpm run e2e:core` (includes `e2e/tests/core/chapter-annotations.spec.ts`).

Output: `shots/checkpoint-1v/`, then a `**Result (<date>, HEAD <sha>) — PASS|FAIL**`
block appended here.

**Result (2026-10-03, HEAD 5072646e) — PASS**

- E2E: `pnpm run e2e:core` — 28/28 green, `chapter-annotations.spec.ts` included.
- Chapter `chapitre-simple-3`, `confirmed`: two drafts via « Annoter » → banner « 2 annotations »; 140+ char root posted → « 2 annotations » on the comment, banner gone, pop-up lists both passages with their bodies (`01`–`03`).
- Same chapter, `moderator`: plain root (no drafts) posted and listed, no annotation count (`04`).
- Reply by `confirmed` under the moderator's root: posted and nested (`05`).
- News `actualite-e2e`: root posted and listed; no annotation banner, form or count button on the page (`06`).
- No HTTP ≥400 or page error during the run.

---

## Phase 2 — v1 leftovers (assumption A5)

**Goal.** Close the three v1 gaps: the highlight cap comes from the policy only,
annotation counts are zero for types without annotation support, and a direct
test proves a beta reader sees no count.

Architecture: §3.2 (bullets on item validation and `visibleCounts`).
Phase 1 left `Private/Support/AnnotationItemValidator.php`, which already reads
the highlight cap from the policy.

**Deliverables.**
- `Private/Requests/StoreCommentRequest.php`: `annotations.*.highlighted_text`
  becomes `['required', 'string']` (drop `max:500`); the cap is enforced by
  `AnnotationItemValidator` through `CommentPublicApi::create`.
- `Private/Services/AnnotationAccessService.php::visibleCounts()`: return the
  all-zero map up front when `$this->policies->supportsAnnotations($entityType)`
  is false, for every viewer (moderators included).

**Tests.**
- `Tests/Feature/Annotations/PostCommentWithAnnotationsTest.php`, new cases:
  - `it('reads the highlight cap from the policy, not the request')` — register a
    test policy (pattern in `AnnotationPolicyRegistryTest.php`) whose highlight
    max is 800 and `canAnnotate`/`canCreateRoot` true: a 600-char highlight is
    accepted; a 801-char one is refused under `annotations`.
  - `it('refuses a 501-char highlight on a chapter under the annotations key')`.
- `Tests/Feature/Annotations/AnnotationCountTest.php`, new cases:
  - `it('gives a zero count to a moderator on a type without annotation support')`
    — register a test policy with `supportsAnnotations()` false, insert an
    annotation row directly under one of its comments, assert count 0 for a
    moderator and for an author.
  - `it('gives a beta reader a zero count')` — chapter with an annotated root
    comment; a user added as beta reader (`addCollaborator(..., 'beta-reader')`
    — check the role string used in Story tests) sees `annotationCount` 0.

**Acceptance.**
- ✅ A 600-char highlight is accepted when the policy allows 800; nothing in `StoreCommentRequest` caps it.
- ✅ A chapter highlight of 501 chars is refused under `annotations`, nothing written.
- ✅ A moderator gets count 0 on a type whose `supportsAnnotations()` is false.
- ✅ A beta reader gets count 0 on a chapter with annotations.
- ✅ `pnpm run gate` green.

---

## Phase 3 — Story: lift A5, expose the annotation mode

**Goal.** A reader who already has a root comment on a chapter may annotate
(server side), and the chapter page knows whether the toolbar writes drafts or
pending changes — while the toolbar stays draft-only until phase 8 wires the
pending mode.

Architecture: §1.1 (Story bullets), §7 #4.

**Deliverables.**
- `app/Domains/Story/Private/Services/ChapterCommentPolicy.php::canAnnotate()`:
  `return $userId > 0 && !$this->chapters->isUserAuthorOfChapter($entityId, $userId);`
  (no longer requires "no root comment"; an emptied root still qualifies,
  decision #6). Update the docblock.
- `Public/Api/CommentPublicApi.php::validateAnnotations()`: the
  create-with-root refusal must stay under the `annotations` key, so the guard
  becomes `parentCommentId !== null || !canAnnotate(...) || !canCreateRoot(...)`.
  (Architecture §1.1 relies on the later `canCreateRoot` check; that check
  answers under `body`, which would break the v1 contract "any refusal rejects
  the whole post under the `annotations` key". Adding `canCreateRoot` here keeps
  it.)
- `app/Domains/Story/Private/Controllers/ChapterController.php::show()`: compute
  - `$rootComment = $userId ? ($this->comments->getRootCommentsByAuthorAndEntities('chapter', $userId, [$chapter->id])[$chapter->id] ?? null) : null`
    (existing `CommentPublicApi` method; inject `CommentPublicApi` if the
    controller does not have it yet — the Story → Comment Public edge exists);
  - `'annotationMode' => $rootComment ? 'pending' : 'draft'`, `'rootCommentId' => $rootComment?->id`;
  - **temporary gate**: `'canAnnotate' => $canAnnotate && $rootComment === null`
    so the toolbar still only appears in draft mode. Comment it
    `// v2 phase 8 removes the draft-only gate`. Phase 8 removes it.
- `Private/Resources/views/components/annotable.blade.php`: new props
  `annotationMode = 'draft'`, `rootCommentId = null`; render
  `data-annotation-mode="{{ $annotationMode }}"` and, when set,
  `data-root-comment-id="{{ (int) $rootCommentId }}"` on the root element.
- `Private/Resources/views/components/annotation-form.blade.php`: same two props,
  same two data attributes on the component root (read by phase 8 through
  `$root.dataset`).
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`: pass
  `:annotation-mode="$annotationMode" :root-comment-id="$rootCommentId"` to
  `<x-comment::annotable>` and `<x-comment::annotation-form>`.

**Tests.**
- `app/Domains/Story/Tests/Feature/Chapters/ChapterCommentPolicyIntegrationTest.php`:
  - split the existing `canAnnotate: false for …` case: keep author, co-author,
    user id 0 → false;
  - `it('canAnnotate: true for a reader who already posted a root comment')`;
  - `it('canAnnotate: true for a reader whose root comment was emptied by a moderator')`
    (use the Comment public API / service path the moderation tests use to empty);
  - `it('canAnnotate: true for a beta reader')` — beta readers are readers
    (spec §3).
- `Tests/Feature/Annotations/PostCommentWithAnnotationsTest.php`: the existing
  `rejects annotations from the chapter author, a co-author, and a reader who
  already has a root comment` stays green unchanged (proves the added guard).
- `app/Domains/Story/Tests/Feature/Chapters/ChapterAnnotateButtonViewTest.php`:
  - `it('renders data-annotation-mode="draft" and no root id for a reader without a root comment')`;
  - `it('renders data-annotation-mode="pending" and the root comment id for a reader with one')`;
  - `it('still hides « Annoter » for a reader with a root comment')` (temporary
    gate; phase 8 flips this assertion).

**Acceptance.**
- ✅ `canAnnotate` is true for a reader with a live or emptied root, false for author/co-author/guest.
- ✅ Posting a second root with annotations is still refused under `annotations`, nothing written.
- ✅ The chapter page carries the right `data-annotation-mode` / `data-root-comment-id`.
- ✅ No visible change on the chapter page for anyone.
- ✅ `pnpm run gate` green.

---

## Phase 4 — `saveChanges`: atomic post-publish add / edit / delete

**Goal.** A commenter can apply adds, edits and deletes to the annotations under
their own root comment in one atomic `PUT`, with errors keyed per item.

Architecture: §2.3 (rows "Commenter saves a delete/edit"), §3.1 (`saveChanges`,
new DTOs), §3.2 (`applyChanges`), §3.3 (row `saveChanges`), §3.5 (PUT route,
body, responses), §7 #3, #6.

Earlier phases left: `Private/Support/AnnotationItemValidator::firstError()`
(phase 1); `ChapterCommentPolicy::canAnnotate` true for a reader with a root
(phase 3).

**Deliverables.**
- `Public/Api/Contracts/AnnotationToCreateDto.php`: add optional trailing
  `public readonly ?string $clientKey = null` (v1 path passes nothing).
- New `Public/Api/Contracts/AnnotationChangeSetDto.php`:
  `AnnotationToCreateDto[] $adds` (each with `clientKey`), `array<int,string> $edits`
  (id => body), `int[] $deletes`.
- `Private/Services/AnnotationService.php`:
  `applyChanges(int $commentId, int $authorId, AnnotationChangeSetDto $changes): void`
  — one `DB::transaction`: deletes (soft-delete each root and its replies, same
  query as `moderatorDelete`), then edits (`body` sanitized with
  `CommentBodySanitizer::ANNOTATION`, `is_processed = false`, `processed_at = null`,
  anchor columns untouched), then adds (reuse `createForComment`).
  Validation is done by the caller before this runs.
- `Public/Api/AnnotationPublicApi.php::saveChanges(int $commentId, int $byUserId, AnnotationChangeSetDto $changes): AnnotationListDto`:
  1. `CommentService::getComment($commentId)` → `ModelNotFoundException` (404) if unknown/trashed.
  2. `AuthorizationException` (403) unless the comment is a root
     (`parent_comment_id === null`), its `author_id === $byUserId`, and
     `CommentPolicyRegistry::canAnnotate(type, entityId, $byUserId)`.
  3. Collect **all** item errors, then throw one `ValidationException` if any:
     - each edit/delete id must be a live **root** annotation of **this**
       comment written by `$byUserId`; otherwise `edits.<id>` / `deletes.<id>`
       => `comment::annotations.errors.stale` (covers deleted-meanwhile,
       foreign, reply ids and ids under another comment alike — no 403, so a
       stale item and a foreign one look the same);
     - an id present in both `edits` and `deletes` → `edits.<id>` stale;
     - edit bodies via `AnnotationItemValidator::firstError($type, $body, null, null, null)` → `edits.<id>`;
     - adds via `firstError($type, body, highlighted, prefix, suffix)` → `adds.<clientKey>`.
  4. `applyChanges`, then return `getForComment($commentId, $byUserId)`.
- New `Private/Requests/SaveAnnotationChangesRequest.php` (shape only):
  `adds` nullable array; `adds.*.key` required string max 64 distinct;
  `adds.*.body` required string; `adds.*.highlighted_text` required string;
  `adds.*.prefix|suffix` nullable string; `edits` nullable array;
  `edits.*.id` required integer distinct; `edits.*.body` required string;
  `deletes` nullable array; `deletes.*` integer distinct. Shape errors stay
  positional (client bugs); item errors are keyed by the API.
- `Private/Controllers/AnnotationController.php::save(SaveAnnotationChangesRequest $request, int $commentId): JsonResponse`
  — builds the DTO, calls the API, returns `200` with `->toArray()` of the list.
- `Private/routes.php` (inside the existing `comments.` group with `web, auth, compliant`):
  `Route::put('/{commentId}/annotations', [AnnotationController::class, 'save'])->whereNumber('commentId')->name('annotations.save');`
- `Private/Resources/lang/fr/annotations.php`: `errors.stale` = « Cette
  annotation n'existe plus. Retirez-la de vos modifications. ».
- An empty change set is a valid no-op (200 with the list).

**Tests.** New `Tests/Feature/Annotations/SaveAnnotationChangesTest.php`
(reader with a root comment on a chapter, as a real user):
- `it('applies adds, edits and deletes in one call and returns the refreshed list')`
- `it('resets the processed flag when the commenter edits a processed annotation')`
- `it('leaves highlight, prefix and suffix unchanged on edit')`
- `it('soft-deletes the replies of a deleted annotation')` (insert a reply row directly)
- `it('writes nothing when one delete id is stale and names it under deletes.<id>')`
- `it('writes nothing when one add is invalid and names it under adds.<key>')`
- `it('refuses an edit of another user’s annotation, of a reply, and of an annotation under another comment as edits.<id>')`
- `it('returns 403 when the user is not the root comment’s author')` (another reader, the chapter author, a moderator)
- `it('returns 403 for a reply comment id')`
- `it('returns 404 for a trashed root comment')`
- `it('works after a moderator emptied the root comment')`
- `it('returns 403 on a news comment')` (`NewsCommentPolicy::canAnnotate` is false)
- `it('returns 401/redirect for a guest')` — follow the convention of `GetAnnotationsEndpointTest.php`
- `it('lets a non-confirmed user save like a confirmed one')`
- `it('dispatches no domain event')` (`Event::fake()` + assert nothing dispatched, pattern in `PostCommentWithAnnotationsTest`)

**Acceptance.**
- ✅ `PUT /comments/{id}/annotations` by the commenter applies all three kinds atomically and returns 200 + list.
- ✅ Any invalid or stale item → 422 keyed `adds.<key>` / `edits.<id>` / `deletes.<id>`, database unchanged.
- ✅ A user other than the root's author gets 403; a trashed root gets 404.
- ✅ A saved edit sets `is_processed` false and `processed_at` null.
- ✅ `pnpm run gate` green.

---

## Phase 5 — Replies, read side

**Goal.** The annotations list returns each root's replies (oldest first) to the
commenter, authors and moderators, hides replies of deactivated writers, and
carries the per-row `can_edit` / `can_reply` / `can_delete` hints.

Architecture: §2.3 (row "User deactivated"), §3.1 (`getForComment` new fields),
§3.2 (`getRepliesForRoots`, `canReply`, read-time filter), §3.3 (reply rows),
§7 #2, #5.

Earlier phases left: `AnnotationPublicApi::getForComment` returning roots only
with `replies: []`; the commenter role restricts **roots** to the viewer's own
(`AnnotationAccessService::restrictToAuthorId`).

**Deliverables.**
- `Private/Services/AnnotationService.php::getRepliesForRoots(array $rootIds): Collection`
  — one query, `repliesOnly()` scope, `whereIn('parent_annotation_id', $rootIds)`,
  ordered `created_at, id`. Not restricted by author (the commenter must see the
  authors' replies under their own roots).
- `Private/Services/AnnotationAccessService.php`:
  - `filterActiveReplyWriters(Collection $replies): Collection` — distinct
    non-null `author_id`s → **one** `AuthPublicApi::getUsersById($ids)` call;
    drop replies whose writer has `isActive === false`. Replies with
    `author_id === null` (deleted user, anonymised) are kept.
  - `canReply(string $viewerRole, CommentAnnotation $root, Collection $visibleReplies, int $viewerId): bool`
    — author role: true; commenter role: `$root->author_id === $viewerId` and
    some visible reply has `author_id !== null && author_id !== $root->author_id`;
    moderator: false.
- `Public/Api/Contracts/AnnotationDto.php`: add `bool $canEdit`, `bool $canReply`
  (constructor + `toArray()` as `can_edit`, `can_reply`). Update the `$replies`
  docblock. Every `new AnnotationDto(` call site must be updated (grep).
- `Public/Api/AnnotationPublicApi.php::getForComment()`: fetch replies for the
  visible root ids, filter them, fetch profiles for root **and** reply writers in
  one `getPublicProfiles` call, build reply DTOs (`replies: []`,
  `highlightedText: ''`, `isProcessed: null`, `canMarkAsProcessed: false`,
  `canEdit: false`, `canReply: false`, `canDelete`: moderator **or** reply
  writer === viewer) and set on each root: `canEdit` = commenter role and owns
  the row; `canReply` per `canReply()`; `canDelete` unchanged (moderator).

**Tests.** New `Tests/Feature/Annotations/AnnotationRepliesTest.php`, list section
(`GET /comments/{id}/annotations`; seed replies as rows directly — phase 6 adds
the endpoint):
- `it('returns replies oldest first under their root for the commenter, an author and a moderator')`
- `it('shows the commenter the author replies under their own roots')`
- `it('hides a reply whose writer is deactivated and shows it again after reactivation')`
  (use the Auth public API / helper the other deactivation tests use)
- `it('keeps the reply of a deleted user, anonymised')`
- `it('sets can_edit only for the commenter on their own roots')`
- `it('sets can_reply for an author on every root, for the commenter only after an author reply, never for a moderator')`
- `it('re-locks the commenter when the only author reply is from a deactivated writer')`
- `it('sets can_delete on a reply for its writer and for moderators only')`
- `it('fetches writer statuses with a single auth call')` — only if cheap
  (`DB::enableQueryLog` count on `users`); otherwise skip and note it.
- Existing `GetAnnotationsEndpointTest.php` stays green (update only for the two new keys if it asserts exact JSON).

**Acceptance.**
- ✅ The list JSON has `replies[]` per root and `can_edit` / `can_reply` on every item.
- ✅ A deactivated writer's reply is absent from everyone's list; it is back after reactivation; no row was modified.
- ✅ `can_reply` follows decision #5 / §7 #2 for each role.
- ✅ `pnpm run gate` green.

---

## Phase 6 — Replies, write side

**Goal.** Authors (any root) and the commenter (once an author replied) can post
a reply; a writer can delete their own reply; moderators' existing delete works
on replies.

Architecture: §2.3 (reply rows), §3.1 (`reply`, `deleteOwnReply`), §3.3,
§3.5 (POST/DELETE routes).

Earlier phases left: `AnnotationAccessService::canReply()` and
`filterActiveReplyWriters()`, `AnnotationService::getRepliesForRoots()` (phase 5);
`AnnotationItemValidator::firstError()` with `null` highlight = body only (phase 1).

**Deliverables.**
- `Private/Services/AnnotationService.php`:
  `createReply(CommentAnnotation $root, int $authorId, string $body): CommentAnnotation`
  (`comment_id` = root's, `parent_annotation_id` = root id, anchor columns null,
  body sanitized `ANNOTATION`); `deleteReply(CommentAnnotation $reply): void` (soft delete).
- `Public/Api/AnnotationPublicApi.php`:
  - `reply(int $parentAnnotationId, int $byUserId, string $body): AnnotationDto` —
    `getAnnotation` (404); `getComment` of its comment (404 if trashed);
    parent is a reply → `ValidationException` `body` `errors.reply_to_reply`;
    resolve role; role `null` or moderator → 403; `canReply()` with the
    filtered visible replies false → 403; body via validator → 422 `body`;
    create; return the DTO shaped as in phase 5 (writer may delete).
  - `deleteOwnReply(int $replyId, int $byUserId): void` — 404 if unknown; 403
    unless `parent_annotation_id !== null` and `author_id === $byUserId`.
- New `Private/Requests/StoreAnnotationReplyRequest.php`: `body` required string.
- New `Private/Controllers/AnnotationReplyController.php`: `store` → `201` +
  DTO `toArray()`; `destroy` → `204`.
- `Private/routes.php`, same group:
  `POST /annotations/{annotationId}/replies` → `annotations.replies.store`;
  `DELETE /annotations/replies/{replyId}` → `annotations.replies.destroy`
  (`whereNumber` on both; the moderator `DELETE /annotations/{annotationId}`
  keeps its `whereNumber`, so `replies/…` cannot collide).
- Lang `errors.reply_to_reply` = « On ne peut pas répondre à une réponse. ».

**Tests.** `Tests/Feature/Annotations/AnnotationRepliesTest.php`, write section:
- `it('lets the chapter author and a co-author reply to any root annotation')` → 201
- `it('refuses a beta reader with 403')`
- `it('refuses the commenter before any author reply and accepts after one')`
- `it('refuses a moderator with 403')`
- `it('refuses another reader with 403')`
- `it('refuses a reply to a reply with 422')`
- `it('refuses an empty body and a body over 1000 characters with 422')`
- `it('returns 404 for a deleted root annotation')`
- `it('lets the writer delete their own reply')` → 204, row soft-deleted
- `it('refuses deleting another user’s reply, and a root annotation through the reply route, with 403')`
- `it('lets a moderator delete a reply through the existing route without touching siblings')`
  (extend or mirror `ModeratorDeleteAnnotationTest.php`)
- `it('dispatches no notification and no event')` (`Notification::fake()`, `Event::fake()`)

**Acceptance.**
- ✅ `POST /comments/annotations/{id}/replies` follows decision #5 for every role (201 or 403).
- ✅ `DELETE /comments/annotations/replies/{id}` deletes only the caller's own reply.
- ✅ Moderator delete of a reply removes that reply only.
- ✅ `pnpm run gate` green.

---

## Phase 7 — JS infrastructure: `annotationChanges` slot + API calls

**Goal.** The comment-draft store holds per-(user, entity) pending
post-publish changes in a new slot, without losing v1 payloads, and `api.js`
can call the three new endpoints.

Architecture: §4 (bullet "Comment-draft store"), §7 #1.

Earlier phases left the endpoints `PUT /comments/{id}/annotations`,
`POST /comments/annotations/{id}/replies`, `DELETE /comments/annotations/replies/{id}`
(phases 4, 6). No UI uses them yet.

**Deliverables.**
- `Resources/js/comment-draft/index.js`:
  - `SCHEMA_VERSION = 2`; `emptyState()` gains
    `annotationChanges: { adds: [], edits: {}, deletes: [] }`;
  - `load()` accepts version 1 **and** 2 (v1 → empty `annotationChanges`,
    other slots kept); filters malformed items;
  - `isEmpty` accounts for the new slot;
  - slot API, exported and exposed on `window.commentDrafts`, mirroring the
    annotations slot: `getAnnotationChanges`, `addPendingAnnotation({body, highlighted, prefix, suffix}) → tempId`,
    `updatePendingAdd(tempId, body)`, `removePendingAdd(tempId)`,
    `setPendingEdit(id, body)`, `undoPendingEdit(id)`,
    `setPendingDelete(id)` (also drops a pending edit of that id),
    `undoPendingDelete(id)`, `clearAnnotationChanges()`, `countAnnotationChanges()`;
  - each write dispatches `comment-drafts:annotation-changes-changed` with
    `{ entityType, entityId, count }`;
  - the root consumed marker **does not** touch `annotationChanges`.
  - Update the header comment describing the slots.
- `Resources/js/annotations/api.js`: `saveAnnotationChanges(commentId, {adds, edits, deletes})`
  (maps the slot to the §3.5 body: `adds[].key = tempId`, `edits` → `[{id, body}]`;
  resolves `{ ok: true, list }` on 200, `{ ok: false, status, errors }` on 422/404,
  throws on other failures), `postReply(annotationId, body)`, `deleteReply(replyId)`.

**Tests.**
- `Resources/js/comment-draft/index.test.js`:
  - `it('loads a version-1 payload with its annotations and an empty annotationChanges slot')`
  - `it('adds, edits, deletes and undoes pending changes and counts them')`
  - `it('drops a pending edit when the same id is marked for deletion')`
  - `it('keeps annotationChanges when the root consumed marker clears the root draft')`
  - `it('dispatches comment-drafts:annotation-changes-changed with the count')`
  - `it('scopes the slot per user and per entity')`
- New `Resources/js/annotations/api.test.js`: payload mapping for
  `saveAnnotationChanges`; 422 resolves with the errors object; 404 resolves
  `{ok:false,status:404}`.

**Acceptance.**
- ✅ A localStorage payload written by v1 (version 1) still restores root/reply drafts and annotation drafts.
- ✅ The new slot survives the consumed marker and reloads.
- ✅ `pnpm run gate` green.

---

## Checkpoint 7v — existing drafts survive the store bump

Run by `visual-verifier`. Phase 7 changed the schema of the comment-draft store,
which root comments, comment replies and v1 annotation drafts all use.

- Before checking out phase 7 (or by writing a version-1 payload into
  localStorage by hand): on a chapter, start a root comment and two annotation
  drafts; on another chapter, start a reply to a comment. Reload on phase 7
  HEAD: all three drafts restore.
- Post the root comment with its drafts: root and annotation drafts are
  cleared, the comment has « 2 annotations ».
- News article: a root comment draft restores after reload.
- E2E: `pnpm run e2e:core`.

Output: `shots/checkpoint-7v/`, then a `**Result (<date>, HEAD <sha>) — PASS|FAIL**`
block appended here.

**Result (2026-10-03, HEAD 6fd451ba) — PASS**

- E2E: `pnpm run e2e:core` — 28/28 green.
- Drafts seeded by hand as `version: 1` localStorage payloads on the e2e instance, as `confirmed`, then the page reloaded on phase 7 HEAD.
- Chapter `chapitre-avance-4`: a v1 root body and two v1 annotation drafts restore. The editor holds the body, the banner reads « 2 annotations », the pop-up lists both passages (`01`, `02`).
- Root posted: the store key is removed (root and annotation drafts cleared), and the new comment shows « 2 annotations » (`03`).
- Chapter `chapitre-publie-1`: a v1 reply draft (parent 1) auto-opens the reply composer with its body (`04`).
- News `actualite-e2e`: a v1 root draft restores into the editor (`05`).
- No HTTP ≥400 or page error during the run.

---

## Phase 8 — Toolbar: pending mode + reaction buttons

**Goal.** In the chapter selection toolbar, « Annoter » and three emoji buttons
❤️ 🔥 👍 write into the draft slot before the reader's root comment exists, and
into pending adds after.

Architecture: §1.1 (Story toolbar bullet), §4 (bullets "Toolbar mode", "Emoji
buttons"). Decisions #8, spec §4.1.

Earlier phases left: `data-annotation-mode` / `data-root-comment-id` on
`<x-comment::annotable>` and `<x-comment::annotation-form>` (phase 3), plus a
**temporary** gate in `ChapterController::show` hiding « Annoter » when the
reader has a root comment; the store slot API
`window.commentDrafts.addPendingAnnotation(...)` (phase 7).

**Deliverables.**
- `app/Domains/Story/Private/Controllers/ChapterController.php`: remove the
  phase-3 temporary gate — `canAnnotate` is the policy answer again.
- `Resources/js/annotations/capture-form.js`: on save, read
  `this.$root.dataset.annotationMode`; `draft` → existing `addAnnotation` /
  `updateAnnotation`; `pending` → `addPendingAnnotation` (new capture) /
  `updatePendingAdd` (editing a pending add). Add an **edit-saved** entry
  point used by phase 10: `openForEdit({ id, body, highlighted })` opens the
  form centred with the body only and saves through `setPendingEdit(id, body)`
  (anchor never changes). Listen for a window event
  `annotations:edit-saved-row` with that payload.
- New `Resources/js/annotations/reactions.js`: Alpine component
  `annotationReactions` — `react(emoji)`: read the selection; reuse
  `extractAnchor`, `closestBlock`, `trimRangeToText` and
  `ANNOTATABLE_AREA_SELECTOR` (export a small shared helper from
  `capture-form.js` if needed rather than duplicating), refuse multi-block and
  over-cap selections (no-op), write `<p>EMOJI</p>` into the active slot per
  the closest `[data-annotable]` `data-annotation-mode`, clear the selection.
  Register in `Resources/js/annotations/index.js`.
- New `Private/Resources/views/components/reaction-buttons.blade.php`
  (`<x-comment::reaction-buttons :can-annotate="…" />`): three `<button
  type="button">` with the unicode emoji, `aria-label` / `title` from lang,
  `data-requires-selection-within=".ce-block--text"` (same as
  `annotate-button`), renders nothing when `canAnnotate` is false.
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`: render
  `<x-comment::reaction-buttons :can-annotate="$canAnnotate" />` in the
  `toolbar-actions` slot right after `<x-comment::annotate-button>`.
- Lang `reactions.heart|fire|thumbs_up` = « Réagir avec un cœur » / « Réagir
  avec une flamme » / « Réagir avec un pouce levé ».

**Tests.**
- `app/Domains/Story/Tests/Feature/Chapters/ChapterAnnotateButtonViewTest.php`:
  - flip phase 3's case: `it('shows « Annoter » and the three reaction buttons to a reader with a root comment')`;
  - `it('shows the reaction buttons with accessible labels to a reader without a root comment')`;
  - `it('shows no reaction buttons to the author, a guest, or on a page where canAnnotate is false')`.
- `Resources/js/annotations/capture-form.test.js`:
  - `it('stores a pending add in pending mode and a draft in draft mode')`;
  - `it('saves an edit of a saved row as a pending edit, body only')`.
- New `Resources/js/annotations/reactions.test.js`:
  - `it('writes <p>❤️</p> as a draft in draft mode and as a pending add in pending mode')`;
  - `it('stores the anchor (highlighted, prefix, suffix) of the selection')`;
  - `it('ignores a selection spanning two blocks and one over the highlight cap')`;
  - `it('clears the selection after reacting')`.

**Acceptance.**
- ✅ A reader with a root comment sees « Annoter » + ❤️ 🔥 👍 on selection; one click stores a pending add with `<p>❤️</p>` and no form opens.
- ✅ A reader without a root comment gets a draft (v1 banner count goes up).
- ✅ Emoji buttons carry French `aria-label`s; refused selections hide them like « Annoter ».
- ✅ `pnpm run gate` green.

---

## Phase 9 — Save banner

**Goal.** While pending changes exist, a sticky bottom banner « Vous avez {N}
annotation(s) non sauvegardée(s) » saves them all in one request or discards
them.

Architecture: §4 (bullet "Save banner"), §3.5 (responses). Spec §4.2 items 5–6, A3.

Earlier phases left: the `annotationChanges` slot + `comment-drafts:annotation-changes-changed`
event and `api.saveAnnotationChanges()` (phase 7); pending adds written by the
toolbar (phase 8); the root comment id in `data-root-comment-id` on
`[data-annotable]` (phase 3).

**Deliverables.**
- New `Resources/js/annotations/changes-banner.js`: Alpine component
  `annotationChangesBanner` (registered in `index.js`). Reads user/entity from
  its own data attributes and the root comment id from the page's
  `[data-annotable]` (`data-root-comment-id`); stays hidden without it. State:
  `count`, `saving`, `error`, `itemErrors` (key → message). `save()` → one
  `saveAnnotationChanges`; on 200: `clearAnnotationChanges()`, dispatch
  `annotations:list-refreshed` `{ commentId, list }` (phase 10 consumes it) and
  update the comment's `[data-annotations-count]` text if present; on 422:
  keep everything, set `itemErrors`, dispatch `annotations:save-errors`
  `{ commentId, errors }`, show a summary naming the items; on 404: mark every
  item stale (same event, every key); network error: generic message.
  `discardAll()` with `confirm()` → `clearAnnotationChanges()`. « Voir » button
  dispatches `annotations:open` `{ commentId }` (so a commenter with zero saved
  annotations can still review pending adds).
- New `Private/Resources/views/components/partials/annotation-changes-banner.blade.php`:
  `x-data="annotationChangesBanner"`, `x-show="count > 0"`, classes
  `sticky bottom-0 z-30` + responsive padding, `role="status" aria-live="polite"`,
  buttons « Enregistrer », « Voir », « Tout annuler ».
- `Private/Resources/views/components/comment-list.blade.php`: include the
  partial when `$annotationsEnabled` and the viewer is logged in, **outside**
  the root `<form>` (after the list, before the modal include).
- `Private/Resources/views/components/partials/comment-item.blade.php`: render
  the « N annotations » button for the **viewer's own root comment** even at
  count 0 (hidden with `x-show`/`hidden` when 0) so the banner can reveal it
  after the first save. Other comments unchanged.
- Lang `changes_banner.text` (plural: « {1} Vous avez :count annotation non
  sauvegardée|[2,*] Vous avez :count annotations non sauvegardées »),
  `changes_banner.save`, `.show`, `.discard`, `.discard_confirm`,
  `.saving`, `.error_items`, `.error_generic`.

**Tests.**
- New `Resources/js/annotations/changes-banner.test.js`:
  - `it('shows the plural-aware count and hides at zero')`
  - `it('sends one PUT and clears the slot on 200')`
  - `it('keeps the slot and dispatches keyed errors on 422')`
  - `it('marks every pending item stale on 404')`
  - `it('discards all after confirmation')`
- `Tests/Feature/RenderCommentListComponentTest.php` (or the file that renders
  `comment-list` today):
  - `it('renders the changes banner outside the root form on a chapter for a logged-in user')`
  - `it('renders no changes banner on a news list and for a guest')`
  - `it('renders a hidden annotations button on the viewer’s own root comment at count 0')`

**Acceptance.**
- ✅ With pending changes, the banner shows the right French plural and survives a reload.
- ✅ « Enregistrer » sends exactly one `PUT`; on success the banner disappears and the count on the comment updates.
- ✅ On 422 nothing is lost and the offending items are named.
- ✅ No banner on news pages or for guests.
- ✅ `pnpm run gate` green.

---

## Phase 10 — Pop-up, commenter overlay

**Goal.** In the « N annotations » pop-up on their own root comment, the
commenter sees pending markers on saved rows, lists pending adds, can undo per
row, and gets « Modifier » / « Supprimer » that store pending changes.

Architecture: §4 (bullet "Pop-up", first sentence), spec §4.2 items 3–4, A2,
decision #7.

Earlier phases left: list JSON with `can_edit`, `replies[]` per root (phase 5);
store slot API (phase 7); `capture-form.js` listening for
`annotations:edit-saved-row` `{ id, body, highlighted }` (phase 8); banner
dispatching `annotations:save-errors` and `annotations:list-refreshed`
(phase 9).

**Deliverables.**
- `Resources/js/annotations/modal.js` (`annotationsModal`):
  - when `viewer_role === 'commenter'`, compute display rows: server rows with a
    `pending` state (`edited` with the pending body, `deleted`) from the slot,
    plus pending adds (state `added`), refreshed on
    `comment-drafts:annotation-changes-changed`;
  - `edit(row)` → dispatch `annotations:edit-saved-row`; `remove(row)` →
    `confirm()` with « Les réponses seront aussi supprimées. » only when
    `row.replies.length > 0`, then `setPendingDelete(row.id)`;
    `undo(row)` → `undoPendingEdit` / `undoPendingDelete` / `removePendingAdd`;
  - on `annotations:save-errors` show the message on the matching row
    (`adds.<tempId>`, `edits.<id>`, `deletes.<id>`), with « Retirer » that
    undoes that pending item;
  - on `annotations:list-refreshed` replace the cached list for that comment;
  - opening the pop-up with an empty server list but pending adds shows the adds
    (no « Aucune annotation. »).
- `Private/Resources/views/components/partials/annotation-modal.blade.php`:
  markers « Modifiée — non enregistrée », « Sera supprimée », « Ajoutée — non
  enregistrée »; buttons « Modifier », « Supprimer », « Annuler la modification »;
  per-row error line. Bind every user string with `x-text` (bodies stay
  `x-html` only for server-sanitized or locally-sanitized HTML as in v1 —
  pending bodies come from the Quill editor; render them the same way the v1
  drafts modal does).
- Lang `server_modal.pending_edited|pending_deleted|pending_added|edit|undo|remove_stale|delete_with_replies_confirm`.

**Tests.**
- `Resources/js/annotations/modal.test.js`:
  - `it('overlays pending edits and deletes on the commenter’s rows and lists pending adds')`
  - `it('undoes a pending edit, a pending delete and a pending add from the row')`
  - `it('asks for confirmation before deleting a row that has replies, and not otherwise')`
  - `it('shows a save error on the matching row and lets the user remove the item')`
  - `it('replaces the cached list on annotations:list-refreshed')`
  - `it('shows no overlay to an author or a moderator')`

**Acceptance.**
- ✅ The commenter can mark a saved annotation edited or deleted, see it flagged, undo it, and see pending adds — all before saving.
- ✅ Deleting a row with replies asks « Les réponses seront aussi supprimées. ».
- ✅ A stale item after a failed save is flagged on its row and removable.
- ✅ Authors and moderators see the v1 pop-up plus nothing commenter-specific.
- ✅ `pnpm run gate` green.

---

## Phase 11 — Pop-up, replies

**Goal.** Everyone who sees an annotation sees its reply thread; authors and
(once unlocked) the commenter can reply inline; writers delete their own
replies; moderators delete any; authors get a hint after replying.

Architecture: §4 (bullet "Pop-up", reply sentences), spec §4.3, decisions #3, #5, #9.

Earlier phases left: list JSON with `replies[]` (writer profile, date,
`can_delete`) and `can_reply` per root (phase 5); `POST …/replies` returns
`201` + the reply DTO, `DELETE /comments/annotations/replies/{id}` (phase 6);
`api.postReply` / `api.deleteReply` (phase 7); the commenter overlay in
`modal.js` (phase 10).

**Deliverables.**
- New `Resources/js/annotations/replies.js`: pure helpers used by `modal.js` —
  `appendReply(list, rootId, reply)`, `removeReply(list, replyId)`,
  `unlockCommenter(root, viewerId)` (after an author reply arrives, nothing to
  do client-side; the server flag is refreshed on next open — keep this helper
  only if a test needs it).
- `Resources/js/annotations/modal.js`: per root, a thread (oldest first:
  avatar/name via `author_profile.display_name`, date); « Répondre » when
  `can_reply` opens one inline editor at a time (the `inline` Editor preset,
  same component as the capture form — `<x-editor::rich-text>` instance
  rendered once in the modal partial with a fixed id, 1–1000 plain chars,
  « Envoyer » disabled when invalid); « Envoyer » → `postReply`, append the
  returned reply; when `viewer_role === 'author'` show the hint
  « Pour que le lecteur soit notifié, répondez aussi à son commentaire. »;
  reply « Supprimer » when `can_delete`: `confirm()` then `deleteReply` (own)
  or the existing moderator `deleteAnnotation` (moderator role); errors use
  `server_modal.action_error`.
- `Private/Resources/views/components/partials/annotation-modal.blade.php`:
  thread markup, the reply editor, the hint, accessible button labels; mobile
  layout (thread indented with `pl-4 sm:pl-6`, buttons wrap).
- Lang `replies.reply|send|cancel|delete|delete_confirm|author_hint|empty_body|too_long`.

**Tests.**
- New `Resources/js/annotations/replies.test.js` for the pure helpers.
- `Resources/js/annotations/modal.test.js`:
  - `it('renders each root’s replies in date order with writer and date')`
  - `it('shows « Répondre » only when can_reply is true')`
  - `it('posts a reply, appends it and shows the author hint to an author only')`
  - `it('deletes the viewer’s own reply after confirmation via the reply route')`
  - `it('lets a moderator delete a reply through the moderator route')`
  - `it('keeps the editor open with an error when the POST fails')`
- `Tests/Feature/RenderCommentListComponentTest.php` (or the modal's render test):
  `it('renders the reply editor in the annotations modal on a chapter')`.

**Acceptance.**
- ✅ An author replies from the pop-up; the reply appears at once and the hint shows.
- ✅ The commenter sees « Répondre » only after an author reply (on next open), and can then reply.
- ✅ A writer deletes their own reply after confirming; a moderator deletes any reply.
- ✅ No reply UI for moderators besides « Supprimer ».
- ✅ `pnpm run gate` green.

---

## Phase 12 — E2E round trip

**Goal.** One browser spec proves the commenter ↔ author loop end to end, and
v1's orphan e2e helpers are reused or removed.

Architecture: §6 ("VERIFY only" bullet), spec §6 v1 leftovers (A5).

Earlier phases delivered the full feature (toolbar reactions, pending changes,
save banner, pop-up overlay, replies).

**Deliverables.**
- New `e2e/tests/features/annotations-v2.spec.ts` (feature spec, see
  `e2e/tests/features/README.md`): reader with a root comment selects text,
  clicks ❤️ → banner « 1 annotation non sauvegardée » → « Enregistrer » → count
  on the comment; edits it via the pop-up, saves; author opens the pop-up,
  replies, sees the hint; reader reopens, sees the reply and « Répondre »,
  replies; reader deletes their reply. One touch-selection run of the reaction
  (reuse `ChapterPage.touchSelectText`).
- `e2e/pages/ChapterAnnotations.ts`: add the helpers the spec needs (`react`,
  `saveBanner`, `pendingRow`, `reply`); for every helper left unused by v1's
  deleted spec (`evidence`, `storedDrafts`, `storedRootBody`, …), either use it
  in the new spec or delete it. Same for `LoginPage.logout` and the
  `LONG_PARAGRAPH` fixture (keep the fixture if the new spec checks the
  over-cap refusal of an emoji click; otherwise delete it and its seeder use).

**Tests.**
- `e2e/tests/features/annotations-v2.spec.ts` as above.

**Acceptance.**
- ✅ `pnpm run e2e e2e/tests/features/annotations-v2.spec.ts` green with the app running.
- ✅ `pnpm run e2e:core` still green.
- ✅ No page-object method or fixture is left without a caller (`grep` each).
- ✅ `pnpm run gate` green (includes the e2e type-check).

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

VERIFY run 2026-10-04 on HEAD `9a200e98` (+ the fix below).
Spec: `e2e/tests/features/annotations-v2.spec.ts` — 16 tests, all green, plus one
declared `test.fail` for row 3. Shots: `shots/*.png` (taken only with
`E2E_SHOTS_DIR` set). Full `pnpm run e2e` green (44 passed),
`pnpm run gate -- --quick` green.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter, reader without a root comment (desktop) | Selection toolbar: « Citer » (if allowed), « Annoter », ❤️ 🔥 👍; clicking ❤️ adds a draft, v1 banner in the root form counts it | ✅ spec « without a root comment… »; `toolbar-no-root.png` |
| Chapter, reader with a root comment (desktop) | Same toolbar; clicking 🔥 shows the sticky banner « Vous avez 1 annotation non sauvegardée » | ✅ round trip (❤️) + touch test (🔥); `save-banner-plural.png` |
| Chapter, refused selection | Cross-block or over-cap selection hides « Annoter » and the three emojis alike | ❌ over-cap ✅ (« Sélection trop longue » replaces all actions). **Cross-block: « Annoter » and ❤️ 🔥 👍 stay visible** (`cross-block-toolbar.png`); « Annoter » then shows the multi-block error in its form, an emoji click is a silent no-op (nothing stored — asserted). Cause: the shared toolbar (`annotable/toolbar.js`) shows an action when every covered text lies in *some* `.ce-block--text`, not in a single one; Quote's « Citer » uses the same rule. Not fixed (touches the shared toolbar and Quote). Spec keeps the expectation as `test.fail` |
| Save banner, plural + reload | « 2 annotations non sauvegardées »; reload keeps it; « Tout annuler » asks then clears | ✅ confirm text asserted; `save-banner-plural.png` |
| Save banner, success | « Enregistrer » → banner gone, « N annotations » on the comment updated (also from 0) | ✅ 0→1 (round trip), 1→2; `save-success-count.png` |
| Save banner, stale item | Moderator deletes an annotation in another session, commenter saves an edit of it → error names it, row flagged, « Retirer » then save succeeds | ✅ `stale-banner-error.png`, `stale-row-flagged.png` (see DECISIONS A16 for the after-reload case) |
| Pop-up, commenter | Rows show « Modifiée — non enregistrée », « Sera supprimée », pending adds; per-row undo; « Modifier » reopens the form with the body only | ✅ `popup-commenter-pending.png`, `popup-commenter-edit-form.png` (passage read-only, body pre-filled) |
| Pop-up, delete with replies | Confirmation « Les réponses seront aussi supprimées. »; after save, root and replies gone | ✅ refuse keeps the row, accept + save removes row and reply |
| Pop-up, author | Sees all rows + replies, « Répondre » on every root, processed toggle; after an edit by the commenter, « Traitée » is reset | ✅ `popup-author.png`; reset asserted after the commenter's saved edit |
| Pop-up, author reply hint | After « Envoyer », hint about replying on the root comment is visible | ✅ `author-reply-hint.png` |
| Pop-up, commenter before/after author reply | No « Répondre » before; present after reopening | ✅ `popup-commenter-before-reply.png`, `popup-commenter-after-reply.png` |
| Pop-up, moderator | Sees replies, « Supprimer » on a reply removes only that reply; no « Répondre » | ✅ after fix: « Supprimer la réponse » was unreadable (contrast 1.2:1, `danger` + `outline`); now filled `danger` like « Supprimer l'annotation »; contrast ≥ 4.5 asserted; `popup-moderator.png` |
| Pop-up, beta reader | No « N annotations » button at all | n/a browser — PHP: `AnnotationCountTest` « gives a beta reader a zero count », `GetAnnotationsEndpointTest` « a beta reader gets 403 » (button renders only at count > 0 except on the viewer's own root) |
| Emptied root comment | Moderator empties the root; reader can still annotate/react; banner and save work | ✅ moderator empties through the UI, reader reacts and saves; `emptied-root-saved.png` |
| Deactivated reply writer | Deactivate a co-author who replied under an annotation → the reply is hidden for the commenter, other authors and moderators; back after reactivation | n/a browser — PHP: `AnnotationRepliesTest` « hides a reply whose writer is deactivated and shows it again after reactivation » (see A9) |
| Deleted user | Reply of a deleted user still shown, anonymised | n/a browser — PHP: `AnnotationRepliesTest` « keeps the reply of a deleted user, anonymised » |
| News article | No reaction buttons, no banner, no pop-up | n/a browser — PHP: `RenderCommentListComponentTest` « renders no banner, no pop-up and pushes no annotations bundle », « renders no changes banner on a news list », `NewsCommentPolicyTest` « refuses annotations on news » |
| Mobile (≈375 px) | Touch selection shows the emoji buttons; banner does not cover the root form or reply submit buttons; pop-up thread readable, buttons wrap | ✅ `mobile-toolbar.png`, `mobile-banner-reply-form.png` (reply submit and banner boxes do not overlap — asserted), `mobile-popup-commenter.png`, `mobile-popup-author.png`. A commenter with a root has no root form, so that half is moot |
| Accessibility | Emoji buttons announce « Réagir avec un cœur » etc.; banner is a polite live region; reply editor and buttons reachable by keyboard | ✅ emoji found by accessible name throughout; banner `role="status"` + `aria-live="polite"` asserted; « Répondre » → Enter opens the editor, « Envoyer » focused → Enter sends |

Out of scope, seen on the way: reply avatars and the chapter illustration are
broken images in the e2e instance only — `.env.e2e` has
`APP_URL=http://localhost:8081` while the suite serves on `:8080`.

## Open items

1. **Error key for the create-with-root path (phase 3).** Architecture §1.1
   relies on `canCreateRoot` (checked later in `CommentPublicApi::create`) to
   guard posting annotations with a second root, but that check answers under
   `body`, which breaks the v1 test
   `rejects annotations … from a reader who already has a root comment` (expects
   `annotations`). The plan adds `canCreateRoot` to `validateAnnotations` —
   same guard, v1 error key kept. Not a decision change; flagged for review.
2. **Foreign / reply ids in `saveChanges` → 422, not 403 (phase 4).**
   Architecture §6 says "422/403". The plan answers 422 keyed on the item for
   every bad edit/delete id (stale, foreign, reply, other comment) so stale and
   foreign look identical and the client can always name the item (A3); 403 is
   kept for "not the root's author" / `canAnnotate` false.
3. **Root comment id for the banner (phases 3, 9).** The plan reads it from
   `[data-annotable][data-root-comment-id]` (written by Story) rather than
   computing it in `CommentListComponent`, because the viewer's root comment
   may not be on the loaded page of the list. Verified: the lookup uses the
   existing `CommentPublicApi::getRootCommentsByAuthorAndEntities()`. Confirm in
   phase 3 that it returns an emptied (moderated) root — it should, the row is
   kept with an empty body.
4. **« N annotations » button at count 0 (phase 9).** v1 renders it only when
   the count is > 0, so a commenter's first post-publish save would have no
   button to update and pending adds no pop-up to show in. The plan renders it
   hidden on the viewer's own root comment and adds « Voir » to the banner.
   Small addition not spelled out in the architecture.
5. **Beta-reader role string (phase 2).** Check the exact collaborator role
   used by Story tests for beta readers (`addCollaborator(..., ?)`) before
   writing the count test.
6. **Deactivation helper (phase 5).** Use whatever the existing v1 lifecycle
   tests (`AnnotationLifecycleTest.php`) call to deactivate/reactivate a user;
   `AuthPublicApi::getUsersById()` returns `['email', 'isActive']` per id
   (verified), with `isActive` false for unknown ids — only non-null writer ids
   are passed.
7. **WRAP note (not for BUILD).** `vision-spec.md` must be moved to
   `annotations-v3/` at WRAP, not deleted (spec §9).
