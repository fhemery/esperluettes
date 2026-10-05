# Chapter annotations — v3 (in-chapter display) — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) (1–11, A1–A10 settled)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Refactor — purifier custom-emoji class list defined once | S | — | DONE |
| 2 | Refactor — annotation list builder over preloaded collections | S | — | DONE |
| 2v | Checkpoint — comment / quote-note / annotation rendering and the « N annotations » pop-up | S | 1, 2 | DONE |
| 3 | Emptied annotations: column, model, `is_emptied`, edit lock | S | 2 | DONE |
| 4 | Moderator « Vider le contenu » endpoint | S | 3 | DONE |
| 5 | `GET in-entity` read endpoint (`getForEntity`) | M | 2, 3 | DONE |
| 6 | Pop-up: « Vider le contenu » button, emptied rows, « Passage absent du chapitre » badge | M | 4 | DONE |
| 6v | Checkpoint — « N annotations » pop-up for commenter / author / moderator | S | 6 | DONE |
| 7 | Shared infra — `rangeFromCanonical` anchoring helper | S | — | DONE |
| 8 | `<x-comment::annotation-overlay>` component wired into the chapter page (inert) | S | — | DONE |
| 8v | Checkpoint — Quote on the chapter page: reader tint, author heat map + gutter pills, summary | S | 8 | DONE |
| 9 | Overlay store + Highlight-API tint (+ heat suppression) | M | 5, 7, 8 | DONE |
| 9v | Checkpoint — quote tint vs annotation tint, heat on/off, quote/unquote | S | 9 | DONE |
| 10 | Gutter avatars and grouping | M | 9 | DONE |
| 11 | Popover shell + author actions (reply, processed) | M | 10 | DONE |
| 12 | Popover commenter actions (modifier / supprimer / répondre) | M | 11 | TODO |
| 13 | Author filter menu + processed display | S | 11 | TODO |

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
- Lang keys are added in `app/Domains/Comment/Private/Resources/lang/fr/annotations.php`
  by the phase that first uses them (architecture §4.8). French only.
- No events, notifications or audit entries anywhere in this task (A9).

## Code facts verified during PLAN

Recorded so phases need not re-discover them.

- `ChapterCommentPolicy::canAnnotate` = any logged-in non-author of the chapter
  (moderators and beta readers included); `canMarkAsProcessed` = author or
  co-author (`app/Domains/Story/Private/Services/ChapterCommentPolicy.php`).
- `CommentPolicyRegistry` has no "is registered" method; an unknown type falls
  back to `DefaultCommentPolicy` (`supportsAnnotations()` = false).
- `AnnotationAccessService::resolveViewerRole(Comment, viewerId)` precedence:
  commenter (own root) → author → moderator → null.
- `AnnotationPublicApi::getForComment` builds `AnnotationDto`s inline today
  (`can_edit` computed there, not in `AnnotationAccessService`).
- Existing JS: `Comment/Resources/js/annotations/{api,modal,capture-form,drafts,changes-banner,replies,reactions}.js`;
  `window.commentDrafts` (`comment-draft/index.js`) exposes `listAnnotations`,
  `getAnnotationChanges`, `removeAnnotation`, `removePendingAdd`,
  `setPendingDelete`, `undoPendingDelete`, …; events
  `comment-drafts:annotations-changed`, `comment-drafts:annotation-changes-changed`,
  `annotation:open-edit {tempId}`, `annotations:edit-saved-row`,
  `annotations:list-refreshed {commentId, list}`.
- `ANNOTATABLE_AREA_SELECTOR = '.ce-block--text'` is exported by `capture-form.js`.
- `Alpine.store('popover', { openId })` is registered in `Shared/Resources/js/app.js`.
- Quote registers `Alpine.store('quotes')` and `Alpine.store('quoteAggregate')`
  in `Quote/Resources/js/quote/index.js` (pushed to `head-scripts`); quote tint
  = `mark.quote-tint` (`Quote/Resources/js/quote/ui/chapter-highlights.js`).
- Vite entries are listed in the root `vite.config.js` (`laravel({ input: [...] })`);
  a new entry must be added there. Comment has no CSS file: a CSS file imported
  from the JS entry is emitted by `@vite` with it (as `editor-bundle.js` does
  with Quill's CSS).
- Chapter page: `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`,
  `<x-quote::author-heat>` wraps `<article data-quote-article>` (around line 223);
  `$annotationMode` (`'draft'|'pending'`) and `$rootCommentId` are already in scope.
- The five purifier profiles carrying the emoji list: `strict`,
  `strict-with-links`, `multiedit-narrative`, `quote-note`, `annotation`
  (`config/purifier.php`).
- e2e specs touching this area: `e2e/tests/core/chapter-annotations.spec.ts`,
  `e2e/tests/core/chapter-annotation-round-trip.spec.ts`,
  `e2e/tests/core/chapter-comments.spec.ts`. No Quote e2e spec exists.

---

## Phase 1 — Refactor — purifier custom-emoji class list defined once

**Goal.** The `ql-custom-emoji*` class list is declared once in
`config/purifier.php` and reused by the five profiles, with no behaviour change
(spec §4.10, architecture §1.1 "config/purifier.php").

**Deliverables.**
- `config/purifier.php` — a `$customEmojiClasses` array (`'ql-custom-emoji'` +
  the nine `ql-custom-emoji-esper*` names) declared before `return`, spread into
  `Attr.AllowedClasses` of `strict`, `strict-with-links`, `multiedit-narrative`,
  `quote-note`, `annotation` (keep each profile's other classes as they are).
  Must stay `config:cache`-safe (plain array, no closures).
- `app/Domains/Shared/Tests/Unit/PurifierCustomEmojiProfilesTest.php` — new
  (config is cross-domain; Shared owns cross-cutting tests).

**Tests.**
- `PurifierCustomEmojiProfilesTest`: dataset over the five profiles × the nine
  emoji names — `Purifier::clean('<span class="ql-custom-emoji ql-custom-emoji-<name>">x</span>', $profile)`
  keeps both classes. Write it first; it passes before and after the refactor.
- Existing `Comment/Tests/Unit/CommentBodySanitizerTest.php` still green.

**Acceptance.**
- ✅ `grep -c "ql-custom-emoji-esperamour" config/purifier.php` returns 1.
- ✅ New test green for all 45 cases, before and after the change.
- ✅ No other file changed.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Refactor — annotation list builder over preloaded collections

**Goal.** Extract the `AnnotationDto` construction of
`AnnotationPublicApi::getForComment` into one private builder that takes
already-loaded collections, so phase 5's `getForEntity` reuses it. No behaviour
change (architecture §3.1, second paragraph).

**Deliverables.**
- `app/Domains/Comment/Public/Api/AnnotationPublicApi.php` — new private
  `buildItems(Collection $roots, Collection $repliesByRoot, array $profiles, string $role, int $viewerId): array`
  (returns `AnnotationDto[]`; holds the `$profileOf` fallback, `seesProcessed`,
  `canMark`, `canDelete`, `canEdit`, `canReply` derivations now inline).
  `getForComment` becomes: resolve role → load roots → load + filter replies →
  load profiles (one `getPublicProfiles` call) → `buildItems` → `AnnotationListDto`.
- Nothing else.

**Tests.**
- No new test. Existing `Comment/Tests/Feature/Annotations/*` (notably
  `GetAnnotationsEndpointTest`, `AnnotationRepliesTest`,
  `SetAnnotationProcessedTest`, `SaveAnnotationChangesTest`) stay green unchanged.

**Acceptance.**
- ✅ `GET /comments/{id}/annotations` responses are byte-identical for
  commenter / author / moderator (existing tests unchanged and green).
- ✅ `pnpm run gate` green.

---

## Checkpoint 2v — comment / quote-note / annotation rendering and the « N annotations » pop-up

Checks that phases 1 (purifier list) and 2 (annotation builder) changed nothing
visible.

- **e2e**: `e2e/tests/core/chapter-annotations.spec.ts`,
  `e2e/tests/core/chapter-annotation-round-trip.spec.ts`,
  `e2e/tests/core/chapter-comments.spec.ts`.
- **Pages / roles** (screenshots under `shots/checkpoint-2v/`):
  - Chapter page, comment list: a root comment and a reply containing a custom
    emoji render the emoji (reader).
  - Quote: a quote with an emoji in its note, on the profile Citations tab
    (its owner).
  - « N annotations » pop-up on a chapter with annotations and replies, opened
    as the commenter, as the author (processed toggle visible), as a moderator
    (« Supprimer » visible). An annotation body with an emoji renders it.

**Result (2026-10-04, HEAD f07d41d2) — PASS**

- e2e: the three specs pass (10/10 incl. auth setup).
- Emoji data added to the post-run e2e DB (data only, through the real
  `CommentBodySanitizer` / `QuoteNoteSanitizer`): root comment 7 and a reply on
  `chapitre-illustre-7`, annotation 1 and its reply, a quote note for `confirmed`.
- Comment list (commenter, author, moderator): root and reply emoji render
  (background PNG, 22×22) — `*-comment-thread.png`.
- Pop-up: annotation and reply emoji render for all three roles; commenter sees
  Modifier / Supprimer l'annotation and no processed toggle; author sees
  « Marquer comme traitée »; moderator sees « Supprimer l'annotation » —
  `*-annotations-popup.png`.
- Citations tab (owner `confirmed`): note emoji render — `owner-citations-tab.png`.
- `getForComment(7, viewer)` JSON for commenter / author / moderator is
  byte-identical between HEAD and pre-phase `2e8a20c0`
  (`AnnotationPublicApi.php` + `config/purifier.php` swapped back).
- The reply avatar is broken in the pop-up only because the e2e `APP_URL` is
  `:8081` and the app was served on `:8080`; this comes from the environment and
  does not depend on these phases (identical payload before and after).

---

## Phase 3 — Emptied annotations: column, model, `is_emptied`, edit lock

**Goal.** An annotation can carry `content_emptied_at`; the DTO exposes
`is_emptied`, `can_edit` is false on an emptied row, and `saveChanges` refuses
an edit on it as stale (architecture §2, §3.1 last paragraph, §3.3 row 3).

Builds on: phase 2 left a private `buildItems(...)` in `AnnotationPublicApi`
that computes every `AnnotationDto` flag.

**Deliverables.**
- `app/Domains/Comment/Database/Migrations/2026_10_05_000001_add_content_emptied_at_to_comment_annotations.php`
  — nullable `timestamp('content_emptied_at')` after `processed_at`, no index;
  `down()` drops it.
- `app/Domains/Comment/Private/Models/CommentAnnotation.php` — add to
  `#[Fillable]`, cast `'content_emptied_at' => 'datetime'`, `isEmptied(): bool`.
- `app/Domains/Comment/Public/Api/Contracts/AnnotationDto.php` — `bool $isEmptied`
  (constructor param with default `false` so reply DTOs need no change),
  `'is_emptied'` in `toArray()`.
- `AnnotationPublicApi::buildItems` — passes `isEmptied`, and `canEdit` becomes
  `… && !$a->isEmptied()`.
- `app/Domains/Comment/Private/Services/AnnotationService.php::applyChanges` —
  an edit whose id is an emptied root is rejected exactly like a foreign/deleted
  id (`edits.<id>` → stale message). A delete on an emptied root is accepted.
- `app/Domains/Comment/Tests/helpers.php` — if a fixture helper creates
  annotations, add an optional way to set `content_emptied_at` (or set it with
  `->update()` in tests).

**Tests.**
- `CommentAnnotationModelTest` — `isEmptied()` false by default, true once
  `content_emptied_at` is set; cast is a Carbon.
- `GetAnnotationsEndpointTest` — `is_emptied` false on a normal row; on an
  emptied row: `is_emptied` true and `can_edit` false for the commenter.
- `SaveAnnotationChangesTest`:
  - `it rejects an edit on an emptied annotation as stale` → 422 with
    `errors["edits.<id>"]`, body unchanged.
  - `it accepts a delete on an emptied annotation` → row soft-deleted.

**Acceptance.**
- ✅ Migration up/down runs (`migrate:fresh` in the test suite).
- ✅ Commenter `PUT /comments/{id}/annotations` with `edits: {<emptied id>: …}`
  → 422 `edits.<id>`; with `deletes: [<emptied id>]` → 200.
- ✅ `pnpm run gate` green.

---

## Phase 4 — Moderator « Vider le contenu » endpoint

**Goal.** `POST /comments/annotations/{annotationId}/empty-content` replaces a
root annotation's body with « <Annotation supprimée> » for moderators only
(spec §4.8, architecture §2.3 "Emptying", §3.1 `moderatorEmpty`, §3.3, §3.5).

Builds on: phase 3 added `comment_annotations.content_emptied_at`,
`CommentAnnotation::isEmptied()`, and `is_emptied` in `AnnotationDto`.

**Deliverables.**
- `AnnotationService::emptyContent(CommentAnnotation $annotation): void` —
  no-op when already emptied; else `body` ← `e(__('comment::annotations.emptied_body'))`,
  `content_emptied_at` ← `now()`. Anchor fields, `is_processed`,
  `processed_at`, replies untouched.
- `AnnotationPublicApi::moderatorEmpty(int $annotationId, int $byUserId): AnnotationDto`
  — `getAnnotation` (404 when unknown/trashed); a reply → `ValidationException`
  (422); empties; returns the refreshed row built through `buildItems` with role
  `moderator` (load its replies + profiles as `getForComment` does).
- `AnnotationModerationController::empty(int $annotationId): JsonResponse` →
  `{ "annotation": AnnotationDto::toArray() }`.
- `app/Domains/Comment/Private/routes.php` — inside the existing
  `role:moderator,admin,tech-admin` group:
  `Route::post('/annotations/{annotationId}/empty-content', …)->whereNumber('annotationId')->name('annotations.empty-content')`.
- Lang: `emptied_body` = `'<Annotation supprimée>'`.

**Tests.** `app/Domains/Comment/Tests/Feature/Annotations/EmptyAnnotationContentTest.php`:
- `it lets a moderator / admin / tech-admin empty a root annotation` — 200,
  body = escaped placeholder, `is_emptied` true, `content_emptied_at` set.
- `it keeps anchor, replies and processed flag` — prefix/highlighted/suffix,
  reply rows, `is_processed`/`processed_at` unchanged.
- `it is idempotent` — second call 200, `content_emptied_at` unchanged.
- `it refuses a reply with 422`.
- `it returns 404 for an unknown or deleted annotation`.
- `it forbids the commenter, the author, a confirmed reader` → 403; guest → redirect/401.
- `it emits no event` — `Event::fake()` / event-bus log empty.
- After emptying: commenter `GET /comments/{id}/annotations` shows `can_edit`
  false, `is_emptied` true; author sees the placeholder body.

**Acceptance.**
- ✅ A `user-confirmed` non-moderator gets 403 on `POST /comments/annotations/{id}/empty-content`.
- ✅ A moderator gets 422 on a reply id.
- ✅ `pnpm run gate` green.

---

## Phase 5 — `GET in-entity` read endpoint

**Goal.** `GET /comments/annotations/in-entity?entity_type=&entity_id=` returns,
for the viewer, one `AnnotationListDto` per root comment of the entity on which
they are commenter or author, in a constant number of queries (spec §3, §6
privacy row; architecture §3.1 `getForEntity`, §3.2, §3.3 row 1, §3.5).

Builds on: phase 2 left `AnnotationPublicApi::buildItems($roots, $repliesByRoot, $profiles, $role, $viewerId)`;
phase 3 added `is_emptied` to the DTO.

**Deliverables.**
- `CommentService::getRootCommentsFor(string $entityType, int $entityId): Collection`
  — live (non-trashed) root comments, `id` + `author_id` only (via the existing
  repository; add a repository method if needed).
- `AnnotationService::getRootsForComments(array $commentIds, ?int $authorId): Collection`
  — batched sibling of `getRootsForComment`, ordered `comment_id, created_at, id`.
- `AnnotationPublicApi::getForEntity(string $entityType, int $entityId, int $viewerId): array`
  (`AnnotationListDto[]`):
  1. `[]` if `$viewerId <= 0` or `!policies->supportsAnnotations($entityType)`;
  2. roots of the entity; `$isAuthor = policies->canMarkAsProcessed(...)` once;
  3. role per root: own root → `commenter`, else `$isAuthor` → `author`, else
     dropped (moderator / no role never yield a list, never a 403);
  4. one `getRootsForComments` for author roots (no author filter) and own
     roots (filter = viewer) — or one query + in-memory filter; one
     `getRepliesForRoots`; one `filterActiveReplyWriters`; one
     `getPublicProfiles`; then `buildItems` per root;
  5. roots with zero annotations are omitted.
- `app/Domains/Comment/Private/Requests/AnnotationsInEntityRequest.php` —
  `entity_type` required string max 64, `entity_id` required integer min 1.
  (No "registered type" rule: an unknown type yields `[]` via step 1 — see Open
  items.)
- `AnnotationController::inEntity(AnnotationsInEntityRequest)` →
  `{ "lists": [ … AnnotationListDto::toArray() … ] }`.
- `routes.php` — `Route::get('/annotations/in-entity', …)->name('annotations.in-entity')`
  in the `auth` + `compliant` group (no clash: `/{commentId}/annotations` is
  `whereNumber`).

**Tests.** `app/Domains/Comment/Tests/Feature/Annotations/AnnotationsInEntityTest.php`:
- `author sees every visible root's annotations with is_processed` (two
  commenters; co-author too).
- `commenter sees only their own root, is_processed null and never true` — mark
  one processed first; assert the JSON has no `true` for `is_processed`
  (no-leak test, spec §6).
- `beta reader without root / other reader / moderator-not-author get lists []`.
- `moderator who is also a commenter gets only their own root as commenter`.
- `guest is redirected / 401`.
- `trashed root comment excluded`; `emptied root comment's annotations absent`
  (already soft-deleted by v1).
- `deactivated reply writer's reply filtered`; `anonymised commenter (author_id null)`
  present with an empty-name profile.
- `news / unknown entity_type → lists []`; `missing entity_id → 422`.
- `query count is the same for 1 and 5 roots` (`DB::enableQueryLog`, author
  viewer, each root with annotations and a reply).
- `is_emptied` and `can_edit` false reach the commenter for an emptied row.

**Acceptance.**
- ✅ A commenter's response never contains another user's annotation nor a
  non-null `is_processed`.
- ✅ Query count constant across root counts.
- ✅ `pnpm run gate` green.

---

## Phase 6 — Pop-up: « Vider le contenu », emptied rows, « Passage absent du chapitre »

**Goal.** In the existing « N annotations » pop-up, moderators get « Vider le
contenu » on root rows, emptied rows lose « Modifier », and rows whose passage
is gone show « Passage absent du chapitre » (spec §4.8, §4.9; architecture §4.7).

Builds on: phase 4 added `POST /comments/annotations/{id}/empty-content`
(moderators, returns `{annotation}`) and `is_emptied` on every row.

**Deliverables.**
- `Comment/Resources/js/annotations/api.js` — `emptyAnnotation(annotationId)`
  → POST, returns the `annotation` object.
- `Comment/Resources/js/annotations/modal.js`:
  - on open (and after a fetch / `list-refreshed`), re-anchor each row: article
    = `document.querySelector('[data-quote-article]')`; canonical =
    `buildCanonicalText(article, { within: ANNOTATABLE_AREA_SELECTOR })`;
    `findAnchor(canonical.text, { prefix, highlighted, suffix })`; a row whose
    status is `missing` gets `row.missing = true`. No article (non-chapter
    entity) → no badge. Pending adds are not re-anchored.
  - `emptyContent(row)`: confirm (`empty_content_confirm`), call
    `emptyAnnotation`, replace the row in the cache by the returned DTO.
  - « Modifier » hidden when `row.is_emptied` (it already follows `can_edit`;
    make sure the pending-edit path also checks it).
- `Comment/Private/Resources/views/components/partials/annotation-modal.blade.php`
  — « Vider le contenu » button next to « Supprimer » for root rows when the
  viewer is a moderator (`viewer_role === 'moderator'`); badge
  « Passage absent du chapitre » next to the quoted snippet on `row.missing`;
  data attribute for the confirm text.
- Lang: `empty_content`, `empty_content_confirm`, `passage_missing`.

**Tests.**
- `modal.test.js`:
  - `marks a row missing when its triple is absent from the article`, `moved`
    and `ok` rows not marked; no `[data-quote-article]` → nothing marked.
  - `emptyContent replaces the cached row with the server DTO`.
  - `hides Modifier on an emptied row`.
- `api.test.js` — `emptyAnnotation posts to /comments/annotations/{id}/empty-content`.
- `Comment/Tests/Feature/Views/` (or `Annotations/`) render test: the partial
  contains the « Vider le contenu » label and the « Passage absent du chapitre »
  label strings (Alpine-bound, so assert presence in the template).

**Acceptance.**
- ✅ Moderator: « Vider le contenu » on root rows only, never on replies.
- ✅ After emptying, the row shows « <Annotation supprimée> » without reload.
- ✅ A row whose highlighted text was edited out of the chapter shows the badge
  for commenter, author and moderator.
- ✅ `pnpm run gate` green.

---

## Checkpoint 6v — « N annotations » pop-up for commenter / author / moderator

Checks that phase 6's pop-up changes left the v1/v2 pop-up intact.

- **e2e**: `e2e/tests/core/chapter-annotations.spec.ts`,
  `e2e/tests/core/chapter-annotation-round-trip.spec.ts`.
- **Pages / roles** (screenshots under `shots/checkpoint-6v/`):
  - Commenter: pop-up with pending edit / pending delete / pending add rows,
    save banner, reply thread; an emptied row shows the placeholder and
    « Supprimer » only.
  - Author: processed toggle, reply editor opens under the right row.
  - Moderator: « Supprimer » and « Vider le contenu » on root rows, « Supprimer »
    only on replies; confirm dialog; row updated in place.
  - Any role: an annotation whose passage was removed from the chapter shows
    « Passage absent du chapitre »; desktop and mobile widths.

**Result (2026-10-04, HEAD afb040cb) — PASS**

The first run (HEAD cbcf5482) failed: « Vider le contenu » was a `danger` +
`outline` button with pale pink text on near-white, about 1.2:1 contrast.
afb040cb made it a solid `danger` button, like « Supprimer ». This re-run
checks that fix and runs the whole checklist again.

- « Vider le contenu » is readable now. For the moderator, its computed style
  is text `rgb(185,28,28)` on background `rgb(254,226,226)`, a **5.30:1**
  contrast, at desktop and at 390px. That clears WCAG AA (4.5:1) and matches
  the neighbouring « Supprimer l'annotation ». See
  `moderator-popup-buttons.png`, `moderator-missing-desktop.png` and
  `moderator-missing-mobile.png`. These are the only shots re-taken; the other
  shots date from the first run and were checked again in this one.
- e2e: `chapter-annotations.spec.ts` and `chapter-annotation-round-trip.spec.ts`
  pass (8/8 incl. auth setup).
- The data was rebuilt by the same scratch flows on the post-run e2e DB, on
  `chapitre-illustre-7`, root 7. `confirmed` added three annotations through
  the UI and saved them from the banner. Then one data-only edit through
  tinker rewrote the « Epsilon un. … » paragraph. The paragraph was rewritten
  twice. The first rewrite also removed the « au milieu » suffix, which gives
  that row the badge in `moderator-popup-buttons.png`. The final rewrite keeps
  that suffix, so only the rewritten passage's row carries the badge. The app
  was served on `:8080` (`:8081` belongs to another worktree's container), and
  `ctx.route` rewrote `localhost:8081` → `:8080`.
- Commenter (desktop): the emptied row shows « <Annotation supprimée> » with
  « Supprimer l'annotation » only, and the other rows keep « Modifier ». The
  pending edit, pending delete and pending add rows each carry their marker.
  The save banner reads « Vous avez 3 annotations non sauvegardées ». The
  reply thread renders, and no « Traitée » marker leaks to the commenter. See
  `commenter-emptied-and-missing.png`, `commenter-pending-rows*.png` and
  `commenter-save-banner.png`.
- Author: there is a processed toggle on every root row, and it flips to
  « Marquer comme non traitée » with the « Traitée » marker. The reply editor
  opens inside the row that was answered. There is no « Vider » and no
  « Modifier ». See `author-processed-toggle.png` and
  `author-reply-editor-under-row.png`.
- Moderator: « Supprimer l'annotation » and « Vider le contenu » appear on all
  4 root rows; the reply has « Supprimer la réponse » only. The native confirm
  reads « Vider le contenu de cette annotation ? Le passage et les réponses
  sont conservés. » After confirming, the row updates in place: there is one
  POST and no refetch, the passage is kept, the row count is unchanged and
  « Vider » is gone from that row. See `moderator-row-emptied-in-place.png`.
- « Passage absent du chapitre » shows on the rewritten passage's row for the
  commenter, author and moderator, at desktop and at 390px. The dialog fits
  the 390px viewport with no horizontal overflow. A pending add is not
  re-anchored. See `*-missing-desktop.png` and `*-missing-mobile.png`.
- Observation (not a regression; product question): the « au milieu de la
  phrase » annotation also gets the badge, even though its highlighted text is
  still in the chapter, unchanged and unique. Its stored suffix
  (« . Epsilon un. Le paragraphe ») ran into the rewritten paragraph, so both
  steps of `findAnchor` fail. This matches the `missing` definition in
  `01-functional.md` (« not found » = neither the triple nor prefix…suffix).
  It will equally drop that annotation's tint in phase 9.

---

## Phase 7 — Shared infra — `rangeFromCanonical` anchoring helper

**Goal.** A pure Shared helper turns canonical-text offsets into one DOM
`Range` through the `nodeMap` of `buildCanonicalText` (architecture §1.1
Shared, §4.2 second bullet). Shared infrastructure Quote may reuse later.

**Deliverables.**
- `app/Domains/Shared/Resources/js/anchoring/canonical-range.js` —
  `export function rangeFromCanonical(canonical, start, end): Range | null`.
  `canonical` = `{ text, nodeMap }` as returned by `buildCanonicalText`
  (`nodeMap: Array<{start, end, domNode: Text}>`). Start = first nodeMap entry
  containing `start`; end = entry containing `end` (end-exclusive). Offsets that
  fall on a block-boundary newline or an emoji token snap to the nearest text
  node edge inside `[start, end)`. Returns null when `start >= end` or no text
  node is covered. No DOM mutation.
- `app/Domains/Shared/Resources/js/anchoring/canonical-range.test.js`.

**Tests.** (vitest, jsdom)
- single text node; spanning two text nodes inside one `<p>` (with `<strong>`);
  spanning two paragraphs (offset on the `\n`); range starting / ending at an
  emoji token; `toString()` of the range equals `text.slice(start, end)` minus
  boundary newlines; null on empty / out-of-bounds.
- Round trip: `findAnchor` result from a fixture → `rangeFromCanonical` →
  `range.toString()` contains `highlighted`.

**Acceptance.**
- ✅ Helper exported, covered, no consumer yet.
- ✅ `pnpm run gate` green.

---

## Phase 8 — `<x-comment::annotation-overlay>` wired into the chapter page (inert)

**Goal.** The overlay component exists, resolves the viewer's in-chapter role,
and wraps the chapter article inside Quote's author-heat wrapper; its JS entry
is loaded but renders nothing yet (architecture §1.1 Story, §4.1).

**Deliverables.**
- `app/Domains/Comment/Public/View/Components/AnnotationOverlayComponent.php`
  (alias `annotation-overlay` via the existing `comment` component namespace) —
  props `entityType`, `entityId`, `rootCommentId` (nullable), `annotationMode`;
  computes `inChapterRole` with `CommentPolicyRegistry` and `Auth::id()`:
  `author` if `canMarkAsProcessed`, else `commenter` if `canAnnotate`, else
  null (guest, unsupported type). Exposes the in-entity URL
  (`route('comments.annotations.in-entity', …)`), the viewer id.
- `app/Domains/Comment/Private/Resources/views/components/annotation-overlay.blade.php`
  — role null → `{{ $slot }}` only, no script. Active →
  `<div class="relative" data-annotation-overlay x-data="annotationOverlay" data-role data-mode data-root-comment-id data-entity-type data-entity-id data-user-id data-url>`,
  the slot, an empty gutter `<div x-ref="gutter" class="hidden md:block absolute inset-y-0 right-0 w-6 translate-x-full pointer-events-none">`
  (same geometry as Quote's), and `@once @push('scripts') @vite('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js') @endpush @endonce`.
- `app/Domains/Comment/Resources/js/annotations/in-chapter/index.js` — on
  `alpine:init`, `Alpine.data('annotationOverlay', …)` reading its dataset; no
  behaviour yet.
- `vite.config.js` — add the new entry to `input` (root file: required for a
  Vite entry; one line).
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` — inside
  `<x-quote::author-heat>`, wrap `<article data-quote-article …>` in
  `<x-comment::annotation-overlay entity-type="chapter" :entity-id="$vm->chapter->id" :root-comment-id="$rootCommentId" :annotation-mode="$annotationMode">`.
  No controller change.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/AnnotationOverlayComponentTest.php`
  (render the component with `$this->blade(...)` / `actingAs`):
  - author and co-author → `data-role="author"` + gutter + script tag;
  - commenter-capable reader (`user-confirmed`, `user`, beta reader,
    moderator-not-author) → `data-role="commenter"`;
  - guest → slot only, no `data-annotation-overlay`, no script;
  - `entity-type="news"` → slot only.
- `app/Domains/Story/Tests/Feature/Chapters/` — extend the chapter show view
  test (e.g. alongside `ChapterAnnotateButtonViewTest`): author sees
  `data-annotation-overlay` around `data-quote-article`; guest does not; Quote's
  `data-quote-author-heat` still present for the author.

**Acceptance.**
- ✅ Chapter page markup for guests is unchanged.
- ✅ Nothing visible changes for any role (overlay is inert).
- ✅ `pnpm run gate` green (includes the Vite build).

---

## Checkpoint 8v — Quote on the chapter page

Checks that wrapping the article (phase 8) did not disturb Quote's chapter
features, which share the article and the gutter column.

- **e2e**: `e2e/tests/core/chapter-annotations.spec.ts`,
  `e2e/tests/core/chapter-comments.spec.ts`.
- **Pages / roles** (screenshots under `shots/checkpoint-8v/`):
  - Confirmed reader: select text → toolbar → quote; yellow `mark.quote-tint`
    appears and is clickable (opens the quote panel); annotate button and
    reactions still work.
  - Author: heat-map toggle on → gutter pills at the right of the article,
    level with passages; click a pill → passage panel; summary panel « focus »
    scrolls to the passage; toggle off clears.
  - Widths `md`, `lg`, and mobile (no gutter below `md`).
  - Guest: chapter renders as before.

**Result (2026-10-05, HEAD 4025e756) — PASS**

- e2e: `chapter-annotations.spec.ts` and `chapter-comments.spec.ts` pass (9/9
  incl. auth setup).
- Method: a scratch driver flow on the post-run e2e DB, `chapitre-simple-3`,
  served on `:8080` (`ctx.route` rewrites `localhost:8081` → `:8080`). It ran
  once on HEAD and once with `show.blade.php` reverted to the pre-phase
  16e403d8. Article box, `mark.quote-tint` / `mark.quote-heat` boxes and
  gutter-pill positions are **identical** in both, for guest 390/768/1280,
  confirmed 390/768/1024 and author 390/768/1024/1280. The two runs were
  repeated after the data changed, and were identical again. Most screenshots
  are byte-identical. The rest differ only by a popover fade caught mid-way.
- Guest: no `data-annotation-overlay`, and the chapter renders as before
  (`head-guest-*.png`). The 401 on `GET /quotes` for guests is there before
  phase 8 too; it is not a regression.
- Confirmed: the overlay (`data-role="commenter"`) wraps the article. The
  yellow `mark.quote-tint` marks show, and a click opens « Ma citation » at
  every width. Select → toolbar shows « Citer », « Annoter » and the reactions.
  Citer → mini-form → Enregistrer adds a new yellow tint, and clicking it opens
  the panel. « Annoter » opens the capture form. ❤️ closes the toolbar and the
  drafts banner reads « 1 annotation… » (`head-confirmed-*.png`).
- Author: the order is `[data-quote-author-heat] > [data-annotation-overlay][data-role=author] > [data-quote-article]`.
  With the heat on, the pills sit right of the article at `md`, `lg` and 1280
  (article right edge 702/704/960, pills at +4px). They are level with their
  passages (0px off). Pills that share a line are pushed down by the designed
  22px step. A click on a pill opens « Cité par N lecteurice(s) ». Turning the
  toggle off removes every mark and pill. In the summary popover, « focus »
  turns the heat back on, scrolls to the passage and opens the panel, at 1280
  and 390. At 390, both gutters are `display:none` and there are no pills
  (`head-author-*.png`).
- Observation, not phase 8: the run's first quote overlapped confirmed's own
  quote on the same passage. It was saved, and the author sees it as its own pill. But
  the reader's `mark.quote-tint` count did not change, because the new tint
  merged into the existing one. This is the same before phase 8.

---

## Phase 9 — Overlay store + Highlight-API tint (+ heat suppression)

**Goal.** Authors and commenters see the annotation tint on every visible
annotation's passage, live with drafts and pending changes, and hidden while the
author's heat map is on (spec §4.1, §4.2.1–2, §4.3.1–2, §4.6, §4.7.1;
architecture §4.2, §4.3, §4.4, §9 "Script order").

Builds on: phase 5 (`GET /comments/annotations/in-entity` → `{lists}`, each
list `{comment_id, viewer_role, items[]}`), phase 7
(`Shared/Resources/js/anchoring/canonical-range.js::rangeFromCanonical`),
phase 8 (inert `annotationOverlay` component with `data-role/mode/root-comment-id/entity-type/entity-id/user-id/url`,
JS entry `in-chapter/index.js`).

**Deliverables.**
- `Comment/Resources/js/annotations/in-chapter/store.js` — `createOverlayStore()`
  registered as `Alpine.store('annotationOverlay')`:
  `{ role, rows, filters: { hiddenCommenterIds: new Set(), showProcessed: false }, heatOn, get visibleRows() }`.
  - `load(context)`: fetch in-entity once when role = `author`, or `commenter`
    with a root comment id; rows = server items, each tagged with
    `comment_id`, `source: 'server'`.
  - Commenter local merge from `window.commentDrafts`: mode `draft` →
    `listAnnotations` (source `draft`, keyed `tempId`); mode `pending` →
    `getAnnotationChanges`: edits replace the body, adds appended (source
    `pending-add`), pending deletes excluded. Re-read on
    `comment-drafts:annotations-changed` and
    `comment-drafts:annotation-changes-changed`.
  - Replace a server list on `annotations:list-refreshed {commentId, list}`.
  - `visibleRows`: drops filtered commenters, and processed rows unless
    `showProcessed` (author only; commenter rows have `is_processed` null).
    Empty when `heatOn`.
- `Comment/Resources/js/annotations/in-chapter/renderer.js` —
  `renderHighlights(articleEl, rows)`: canonical =
  `buildCanonicalText(articleEl, { within: '.ce-block--text' })`; per row
  `findAnchor` → `ok`/`moved` → `rangeFromCanonical`; `missing` dropped; fills
  `CSS.highlights` entries `annotation` and `annotation-processed`; returns
  `[{ row, range }]` for phase 10. No-op on tint when `CSS.highlights` is
  absent (still returns ranges). Scheduled one `requestAnimationFrame` after any
  change to `quotes.items`, `quoteAggregate.visible`, rows, filters (via
  `Alpine.effect` created in `alpine:init`, so Quote's stores exist).
- `heatOn` mirrors `Alpine.store('quoteAggregate')?.visible` (undefined →
  false).
- `Comment/Resources/js/annotations/in-chapter/overlay.css` imported by
  `index.js`: `::highlight(annotation)` coloured underline (A1) with a
  dark-mode value (`.dark` / `prefers-color-scheme`, matching how the theme
  switches); `::highlight(annotation-processed)` same at reduced opacity (A5).
  Must not set `background-color` (Quote's yellow stays visible underneath).
- `in-chapter/index.js` — wires store + renderer to the overlay component.
- `app/Domains/Quote/AGENTS.md` and `app/Domains/Comment/AGENTS.md` — one line
  each: Comment's in-chapter overlay observes `Alpine.store('quotes').items` and
  `Alpine.store('quoteAggregate').visible`; renaming them silently breaks heat
  suppression (architecture §1.1 Quote, §9).

**Tests.** (vitest)
- `store.test.js`: server-only author rows; commenter draft mode merges drafts;
  pending mode applies edits, appends adds, hides pending deletes; re-reads on
  both change events; `list-refreshed` replaces one list; filters hide a
  commenter; processed hidden unless `showProcessed`; `heatOn` empties
  `visibleRows`; no fetch for a commenter without root; fetch error leaves rows
  empty without throwing.
- `renderer.test.js` (stub `globalThis.CSS.highlights = new Map()` and a
  `Highlight` class): `ok` and `moved` rows registered, `missing` dropped,
  processed rows in `annotation-processed`; rebuild after the article DOM is
  re-wrapped by a `<mark class="quote-tint">` still finds the range; no
  `CSS.highlights` → no throw.

**Acceptance.**
- ✅ Author: every visible annotation's passage underlined on load (all
  breakpoints); processed ones not shown.
- ✅ Commenter: own drafts / pending adds tinted immediately after creation;
  a pending delete un-tints at once; nothing of other users.
- ✅ Heat on → no annotation tint; heat off → tint back.
- ✅ No request to `in-entity` for guests or readers without a root comment.
- ✅ `pnpm run gate` green.

---

## Checkpoint 9v — quote tint vs annotation tint, heat on/off

Checks that the annotation tint (phase 9) coexists with Quote's renderers.

- **Pages / roles** (screenshots under `shots/checkpoint-9v/`):
  - Commenter who also quotes: a passage both quoted and annotated shows yellow
    background + annotation underline; clicking the yellow mark still opens the
    quote panel; quote then unquote a passage overlapping an annotation → the
    underline is still on the right text afterwards.
  - Author: heat on → underline gone, Quote pills and heat tint as in 8v; heat
    off → underline back.
  - Light and dark mode.

**Result (2026-10-05, HEAD 7a13764a) — PASS**

- e2e: `chapter-annotations`, `chapter-comments`, `chapter-annotation-round-trip`
  and `comment-draft-consume` pass (14/14 incl. auth setup).
- Method: a scratch driver flow on the post-run e2e DB, `chapitre-illustre-7`
  (confirmed's root comment 7 carries the annotation « Delta un. Un paragraphe
  avec »; confirmed's quote « de l'italique et du gras » sits next to it).
  Served on `:8080` (`ctx.route` rewrites `:8081` → `:8080`), at 1280 and 390,
  `deviceScaleFactor` 2. No page errors, console errors or HTTP ≥ 400 for any role.
- Tint: `CSS.highlights.get('annotation')` holds exactly the annotated passage.
  `getComputedStyle(p, '::highlight(annotation)')` gives a 2px underline,
  `rgb(29, 78, 216)` in light mode and `rgb(147, 197, 253)` with
  `data-appearance=dark`, and a transparent background (`confirmed-*-load-light.png`).
- Commenter who quotes: quoting « un. Un paragraphe », inside the annotated
  passage, shows the yellow mark and the underline on the same text, in light
  and dark (`confirmed-*-quoted-{light,dark}.png`). After Quote re-wraps the
  article, the highlight range is still exactly « Delta un. Un paragraphe avec ».
  A click on the overlapped yellow mark opens « Ma citation »
  (`confirmed-*-quote-panel.png`). « Supprimer » removes the mark, and the
  underline stays on the same passage (`confirmed-*-unquoted-*.png`).
- Author: with the heat off, the underline shows in light and dark. With the
  heat on, the `annotation` highlight is deleted and the 5 `mark.quote-heat`
  marks show. There are 2 gutter pills at 1280 and none at 390, as in 8v.
  Heat off again: the underline is back and the heat marks are gone
  (`author-{1280,390}-{light,dark}-heat-{off,on,off-again}.png`).
- Observation, not phase 9: when a second quote is added in the same paragraph,
  the existing tint « de l'italique et du gras » loses its leading « de l' ».
  The marks change from `["de l'","italique"," et du ","gras"]` to
  `["un. Un paragraphe","italique"," et du ","gras"]`, even though the store
  still holds the full text. This survives a reload and reverts when the new
  quote is deleted. With `in-chapter/` reverted to 1f9ee3b5 (before phase 9)
  the result is identical (`ab-{head,pre}-*-quoted.png`), so the bug is in
  Quote's tint rendering (`chapter-highlights.js`) and needs its own backlog item.

---

## Phase 10 — Gutter avatars and grouping

**Goal.** At `md`+, each visible annotation shows its annotator's avatar in the
gutter level with the passage, grouped within ~20px with a « +N » badge, hidden
while the heat map is on (spec §4.2.3–5, §4.6, §4.7; architecture §4.5).

Builds on: phase 9 left `Alpine.store('annotationOverlay')` (`visibleRows`,
`heatOn`) and `renderHighlights()` returning `[{row, range}]`; phase 8 left an
empty `x-ref="gutter"` column in the overlay markup.

**Deliverables.**
- `Comment/Resources/js/annotations/in-chapter/gutter.js`:
  - `export function groupByPosition(items, minGap = 20)` — pure; `items`
    `{ key, top, start, visible, isRoot }` sorted by `top` then `start`; one
    group per band where consecutive tops differ < `minGap`; `lead` = first
    visible item in reading order; `extra` = other visible root items; groups
    with no visible item dropped.
  - positioning: `top` = `range.getClientRects()[0].top` − gutter top; rebuilt
    on store change, `resize`, article `ResizeObserver`, image `load` (same
    triggers as `Quote/Resources/js/quote/ui/author-heat.js`).
- Overlay Blade: gutter renders `<template x-for="group in groups">` →
  `<button type="button">` with the lead's avatar (default avatar when
  `avatar_url` is empty — anonymised commenter, A6), « +N » badge,
  `aria-label` naming the commenter and the count (A10); gutter
  `pointer-events-auto` on buttons only; `x-show="!$store.annotationOverlay.heatOn"`.
  Click dispatches `annotation-overlay:open {groupKey}` (consumed in phase 11).
- Lang: `overlay.avatar_label` (`:name`, `:count` with plural), `overlay.more`
  (`+:count`).

**Tests.**
- `gutter.test.js` — `groupByPosition`: two items 10px apart → one group, +1;
  25px apart → two groups; lead skips an invisible first item; replies never
  counted; all-invisible group dropped; stable order on equal tops.
- `Comment/Tests/Feature/Annotations/AnnotationOverlayComponentTest.php` — the
  active markup contains the gutter button template with an `aria-label` binding.

**Acceptance.**
- ✅ Author at `md`+: one avatar per passage / group, aligned with the passage
  after resize and after an image loads; below `md` no avatar.
- ✅ Heat on → avatars hidden; off → back.
- ✅ Avatars are reachable with Tab and announce name + count.
- ✅ `pnpm run gate` green.

---

## Phase 11 — Popover shell + author actions

**Goal.** Clicking a gutter avatar opens a Comment-owned popover on the group
(« 1 / N », arrows) with body, replies, « Répondre » and « Marquer comme
traitée / non traitée » for the author (spec §4.4, §4.2.5; architecture §4.6).

Builds on: phase 10 dispatches `annotation-overlay:open {groupKey}` from gutter
buttons and exposes groups (lead + members) in the overlay; phase 9's store
rows are `AnnotationDto` arrays (`author_profile`, `body`, `replies`,
`is_processed`, `is_emptied`, `can_reply`, `can_mark_as_processed`,
`comment_id`) and the store listens to `annotations:list-refreshed`.

**Deliverables.**
- `Comment/Resources/js/annotations/in-chapter/popover.js` —
  `Alpine.data('annotationPopover')`: opens on `annotation-overlay:open`,
  claims `Alpine.store('popover').openId` (closes when another id takes it),
  positioned next to the avatar with the same placement/clamp as
  `<x-shared::popover>`; teleported to `body`; members = visible rows of the
  group, index « i / N », `<` / `>` buttons; Escape and outside click close and
  return focus to the avatar; closes when `heatOn` turns true or the group
  becomes empty; when the current row becomes hidden (processed while hidden),
  moves to the next visible member.
  - Author actions: « Répondre » with its own editor id (not the modal's
    `annotation-reply-editor`) → `api.postReply` + `replies.appendReply`; after
    a reply, show the v2 hint about replying on the root comment (reuse the
    existing lang key the modal uses). Processed toggle → `api.setProcessed`.
  - After each server mutation: refetch nothing; update the store row and
    dispatch `annotations:list-refreshed {commentId, list}` with the updated
    list so the pop-up cache stays in sync. Error → message in the popover,
    typed reply kept.
  - Emptied rows show the placeholder body (already the server body).
  - No moderation / report action.
- Overlay Blade: popover markup (avatar + name, body via `x-html` of the
  sanitized server body, replies, actions) and the reply editor (`<x-editor::rich-text>`
  with the annotation toolbar preset, as the modal partial does).
- Lang: `mark_processed`, `mark_unprocessed`, `overlay.position` (`:index / :total`),
  `overlay.previous`, `overlay.next`, `overlay.close`.

**Tests.** (vitest, `popover.test.js`)
- opens on the event with the group's visible members; arrows cycle; « 1 / N »
  text; Escape closes and focuses the avatar; claiming `popover.openId` by
  another id closes it.
- processed toggle calls `setProcessed`, updates the row, dispatches
  `annotations:list-refreshed`; with `showProcessed` off the row leaves the
  group and the popover moves on / closes when it was the last.
- reply posts, appends, shows the hint; failure keeps the text and shows the error.
- `heatOn` → closed.

**Acceptance.**
- ✅ Author: popover on click, keyboard operable, processed toggle immediate,
  « N annotations » pop-up shows the same processed state without reload.
- ✅ Commenter's popover shows no processed control (no `can_mark_as_processed`).
- ✅ `pnpm run gate` green.

---

## Phase 12 — Popover commenter actions

**Goal.** In their own popover the commenter can « Modifier », « Supprimer »
(draft or pending change) and « Répondre » when v2's rule allows; emptied
annotations offer « Supprimer » only (spec §4.3.3–6; architecture §4.6 actions
table).

Builds on: phase 11 popover (`annotationPopover`, rows from
`Alpine.store('annotationOverlay')` with `source` = `server` | `draft` |
`pending-add`, reply wiring via `api.postReply`); phase 9's store re-reads
`window.commentDrafts` on `comment-drafts:annotations-changed` and
`comment-drafts:annotation-changes-changed`.

**Deliverables.**
- `in-chapter/popover.js` — commenter actions by row source, mirroring what
  `annotations/modal.js` and `annotations/drafts.js` do for the same row kinds:
  - `draft` → « Modifier »: dispatch `annotation:open-edit {tempId}`;
    « Supprimer »: `commentDrafts.removeAnnotation(userId, type, id, tempId)`.
  - `server` (pending mode) → « Modifier » (only if `can_edit`): dispatch
    `annotations:edit-saved-row` with `{ id, body, highlighted }`;
    « Supprimer »: `commentDrafts.setPendingDelete(...)` (the row disappears
    from text and gutter via the store).
  - `pending-add` → same handlers the modal uses for pending adds (edit via
    the capture form, delete via `removePendingAdd`).
  - « Répondre » when `can_reply` (server rows only), as for the author.
  - `is_emptied` → « Supprimer » only.
- Popover closes / moves on when the edited-away row disappears.

**Tests.** (vitest)
- each source × action dispatches the right event / calls the right
  `commentDrafts` function with `(userId, entityType, entityId, …)`;
- emptied row exposes no « Modifier »; `can_reply` false → no « Répondre »;
- pending delete removes the row from `visibleRows` and closes the popover when
  it was the only member.

**Acceptance.**
- ✅ Commenter in `draft` mode: edit / delete from the popover update the
  drafts modal count and the tint without reload.
- ✅ Commenter in `pending` mode: actions appear in the Save banner; « Annuler »
  (undo in the pop-up) brings the tint back.
- ✅ `pnpm run gate` green.

---

## Phase 13 — Author filter menu + processed display

**Goal.** Authors get, at `md`+ at the top of the article, a menu listing every
commenter (all ticked) and « Afficher les annotations traitées » (off); state
per visit; processed annotations, when shown, are dimmed (spec §4.4.5, §4.5,
§4.6; architecture §4.3 filters, §4.2 `annotation-processed`).

Builds on: phase 9's store `filters: { hiddenCommenterIds: Set, showProcessed }`
already drives `visibleRows`, the tint and (phase 10) the gutter; phase 11's
popover reacts to rows leaving a group.

**Deliverables.**
- `Comment/Resources/js/annotations/in-chapter/filter-menu.js` —
  `Alpine.data('annotationFilterMenu')`: commenters derived from server rows
  (distinct `author_id`, name + avatar; anonymised → `author_id` null grouped
  under the anonymised name with the default avatar, A6); toggles write the
  store's filters (reassign the Set so Alpine reacts).
- Overlay Blade: menu (author role only, `hidden md:block`, top of the
  article), shown only when the author has at least one visible-or-processed
  row (A4), hidden while `heatOn`. Dimmed avatar class for processed members in
  the gutter (phase 10 markup) — `opacity-50`.
- Lang: `filter.title`, `filter.commenters`, `show_processed`.

**Tests.**
- `filter-menu.test.js`: lists each commenter once; unticking hides their rows
  from `visibleRows`; « Afficher les annotations traitées » adds processed rows
  (in the `annotation-processed` highlight); state not persisted
  (no `localStorage` write); hidden when `heatOn`; not rendered for role
  `commenter`.
- `AnnotationOverlayComponentTest`: filter-menu markup present for author,
  absent for commenter.

**Acceptance.**
- ✅ Author unticks a commenter → their tint and avatars disappear; groups
  recompute; reload resets.
- ✅ Processed shown → dimmed underline and dimmed avatar.
- ✅ The « N annotations » pop-up is never filtered.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter, guest | No tint, no gutter avatar, no filter menu; no `in-entity` request | |
| Chapter, reader with no annotation (confirmed, non-confirmed, beta reader) | Nothing annotation-related shown; quote features as before | |
| Chapter, commenter, draft mode | New draft via « Annoter » / reaction → underline + avatar at once; popover « Modifier » opens the capture form; « Supprimer » removes tint + avatar and updates the drafts count | |
| Chapter, commenter, pending mode | Published + pending adds tinted; pending edit body shown in popover; pending delete hides it; Save banner; undo restores | |
| Chapter, commenter, reply | « Répondre » appears only after an author replied; reply posts; failure keeps text | |
| Chapter, commenter, quote overlap | Yellow quote mark + annotation underline on the same words; clicking yellow opens the quote | |
| Chapter, commenter, privacy | No processed hint anywhere; never another reader's annotation | |
| Chapter, author, happy path | All commenters' published annotations underlined; avatars aligned at `md` and `lg`; « +N » on close passages; popover « 1 / N » + arrows | |
| Chapter, author, processed | « Marquer comme traitée » hides it at once (popover moves on / closes); « Afficher les annotations traitées » shows it dimmed; « non traitée » restores | |
| Chapter, author, filter | Untick a commenter → tint + avatars gone, groups recompute; reload resets; pop-up not filtered | |
| Chapter, author, heat map | Heat on → annotation tint, avatars, filter menu, popover gone; heat off → back in the same filter state | |
| Chapter, author, empty state | Chapter with no annotation → no filter menu, no gutter avatar | |
| Chapter, co-author | Same as author | |
| Chapter, moderator not author | No in-chapter display; pop-up has « Supprimer » + « Vider le contenu » (roots only) | |
| Pop-up, moderator « Vider le contenu » | Confirm → « <Annotation supprimée> » in place; replies + processed kept; author and commenter see placeholder; commenter has « Supprimer » only (pop-up and popover) | |
| Pop-up, stale data | Chapter edited so a passage vanishes → no tint/avatar in chapter, badge « Passage absent du chapitre » in the pop-up (all roles); moved passage → tint on new place, no badge | |
| Deleted commenter (anonymised) | Default avatar + anonymised name in gutter, popover and filter list | |
| Deactivated commenter | Their annotations gone from the chapter; deactivated reply writer's replies hidden | |
| Mobile (< `md`) | Author and commenter: underline only, no avatar / popover / filter menu; tapping the underline does nothing; pop-up as before | |
| Resize / images | Avatars follow passages after window resize and after chapter images load | |
| Keyboard / a11y | Tab reaches avatars (label = name + count), Enter opens, arrows buttons labelled, Escape closes and returns focus | |
| Dark mode | Underline and dimmed variant legible; popover readable | |
| Popover exclusivity | Opening a Shared popover / quote panel closes the annotation popover and vice versa | |

## Open items

| # | Item | Phase |
|---|------|-------|
| 1 | Architecture §4.1 and §6 list "moderator" and "beta reader" among viewers who get the slot only, but §4.1's own derivation (`commenter` when `canAnnotate`) and `ChapterCommentPolicy::canAnnotate` (any logged-in non-author) make them `commenter`. The plan follows the derivation: any logged-in non-author gets the commenter overlay, which shows only their **own** annotations (a moderator or beta reader who never annotated sees nothing; one who annotated as a reader sees their own). This keeps spec §3 ("whoever `canAnnotate` lets annotate sees their own") and decision #4 (no display of others' annotations to moderators). Confirm before phase 8, or say if moderators must get nothing even for their own annotations. | 8 |
| 2 | Architecture §3.5 asks `AnnotationsInEntityRequest` to validate that `entity_type` is registered in `CommentPolicyRegistry`; the registry has no such method. The plan validates `required|string|max:64` and lets an unknown type return `lists: []` through the existing `supportsAnnotations()` fallback, instead of adding a registry method. | 5 |
| 3 | `vite.config.js` (root) gets one new input line for `in-chapter/index.js` — outside `app/Domains`, needed by any new Vite entry. | 8 |
| 4 | Theme dark-mode mechanism for `::highlight()` (class on `<html>` vs media query): check how the Shared theme toggles before writing `overlay.css`. | 9 |
| 5 | Exact handlers for editing / deleting a **pending add** from outside the modal: phase 12 mirrors `modal.js`; if the capture form cannot open on a pending add by event, a small exported helper in `capture-form.js` may be needed. | 12 |
