# Chapter Annotations — v1 Implementation Plan

Concrete, phase-by-phase plan for the v1 delivery.

- Functional spec (long-term vision): [`Chapter_Annotations.md`](./Chapter_Annotations.md)
- Architecture (data model, public API, JS structure): [`Chapter_Annotations_Architecture.md`](./Chapter_Annotations_Architecture.md)
- v1 scope summary: [§8.1 of the architecture doc](./Chapter_Annotations_Architecture.md#81-v1-scope-the-only-scope-we-commit-to-right-now)

## Working agreement

- One phase = one PR. Each phase ships independently, passes deptrac + the full test suite, and is revertable.
- Each phase has explicit **deliverables**, **tests**, and **acceptance criteria**. We don't move on until acceptance is met.
- Phase order is fixed unless we find a blocker; in that case we discuss before re-ordering.
- "Manual smoke" items in the acceptance lists are a hard requirement, not a nice-to-have.

## Phase index

| # | Phase | Estimated size | Dependencies |
|---|-------|----------------|--------------|
| [OK] 1 | Editor refactor (`<x-shared::editor>` toolbar prop) | M | — |
| 2 | Root-comment draft local storage | S | 1 |
| 3 | JS test infrastructure | S | — (can run in parallel) |
| 4 | Schema + model | S | — |
| 5 | Policy contract + cascade listeners | S | 4 |
| 6 | PHP services + `AnnotationPublicApi` | M | 4, 5 |
| 7 | Backend endpoints | M | 6 |
| 8 | Moderation topic registration | S | 4 |
| 9 | JS pure core | M | 3 |
| 10 | `<x-comment::annotable>` bootstrap (invisible) | S | 1, 9 |
| 11 | Write mode (toolbar + form + drafts + banner) | M | 10 |
| 12 | Pop-up modal (drafts mode + server mode) | L | 7, 8, 11 |
| 13 | Atomic publish wiring | S | 7, 11, 12 |
| 14 | v1 polish (i18n, a11y, manual QA) | M | 12, 13 |

Total: ~14 PRs. Sizes are rough — S ≈ half a day, M ≈ 1–2 days, L ≈ 2–3 days. They will all be smaller than current annotation feature scope suggests because each is sharply bounded.

---

## Phase 1 — Editor refactor

**Goal.** Replace the boolean toggles on `<x-shared::editor>` (`withHeadings`, `withLinks`, `withSpoiler`) with a single ordered `:toolbar="[...]"` array prop, propagated to `editor-bundle.js`. Migrate every call site.

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/editor.blade.php` accepts `:toolbar` (array, ordered list of feature tokens). Default = a "full" preset matching today's behaviour, to avoid surprising migrated sites that still pass nothing.
- Valid tokens (v1): `'bold'`, `'italic'`, `'underline'`, `'strike'`, `'blockquote'`, `'list'`, `'align'`, `'custom-emoji'`, `'header'`, `'link'`, `'spoiler'`.
- `app/Domains/Shared/Resources/js/editor-bundle.js::initQuillEditor` reads the JSON list off `data-toolbar`, builds the toolbar groups and allowed-formats list dynamically. Backward-compat data attributes (`data-with-headings`, `data-with-links`, `data-with-spoiler`) are removed.
- Every Blade call site of `<x-shared::editor>` is updated. Grep first; expect call sites in Story (story create/edit, chapter create/edit), Comment (root comment, reply), Profile (bio), News (article body), FAQ (answer body), Static Page (page body). One PR migrates them all.

**Tests.**
- PHP: existing form-rendering tests should still pass without change (forms still render).
- Manual smoke pass on each migrated form before merge — confirm each renders the expected toolbar.

**Acceptance.**
- ✅ All editor call sites migrated; no `withHeadings`/`withLinks`/`withSpoiler` references left in the codebase (`grep -r 'withHeadings\|withLinks\|withSpoiler' app/Domains/` returns nothing under views).
- ✅ Every form that uses the editor renders correctly (manual checklist filled).
- ✅ The full test suite passes.

---

## Phase 2 — Root-comment draft local storage

**Goal.** Prevent loss of an in-progress root comment. Independent of annotations, ships value on its own.

**Deliverables.**
- New JS module `app/Domains/Comment/Resources/js/comment-draft/index.js` exporting `loadDraft(key)`, `saveDraft(key, body)`, `clearDraft(key)`. Schema:
  ```json
  { "version": 1, "body": "<p>...</p>", "savedAt": 1736291200000 }
  ```
- New Alpine glue in `comment-list.blade.php`: on root-comment-editor input (debounced ~500 ms), save to `comment:draft:{userId}:{entityType}:{entityId}`. On mount, if a draft exists, restore. On `submit` success of the comment form, clear.
- Vite entry point added (one tiny bundle, conditionally `@push`ed by `<x-comment::comment-list>`).

**Tests.**
- Vitest (phase 3 wiring expected; if not yet ready, ship the JS first and the tests with phase 3): pure function unit tests of `loadDraft` / `saveDraft` / `clearDraft` against `happy-dom`'s `localStorage`. Round-trip, version mismatch discards data, missing key returns `null`.
- Manual smoke: type a comment → reload page → comment is restored. Submit → drafts cleared.

**Acceptance.**
- ✅ Drafts persist across page reload, scoped per user + entity.
- ✅ Submit clears the draft.
- ✅ Logging out and back in as a different user on the same machine does not surface another user's draft (key includes userId).

---

## Phase 3 — JS test infrastructure

**Goal.** Stand up Vitest + happy-dom + testing-library + user-event. Wire into pre-commit and CI. Can land before or in parallel with phase 2.

**Deliverables.**
- `package.json` devDependencies: `vitest`, `happy-dom`, `@testing-library/dom`, `@testing-library/user-event`.
- `vitest.config.js` at repo root with `environment: 'happy-dom'`, `include: ['app/Domains/**/Resources/js/**/*.test.js']`.
- `npm run test` script (alias for `vitest run`).
- One trivial passing test (e.g., `app/Domains/Comment/Resources/js/comment-draft/index.test.js` if phase 2 has shipped; otherwise a smoke test).
- `scripts/husky-precommit.js` runs `npx vitest run` after deptrac, before PHP staged tests.
- CI: add a `vitest run` step (alongside `composer deptrac` and the PHP test suite).

**Tests.**
- Self-validating: the test infra runs at least one test, which passes.

**Acceptance.**
- ✅ `./vendor/bin/sail composer deptrac && npx vitest run` succeeds locally.
- ✅ Pre-commit invokes Vitest and blocks on failure.
- ✅ CI invokes Vitest as a required check.

---

## Phase 4 — Schema + model

**Goal.** Single `comment_annotations` table, Eloquent model, scopes, model-level tests.

**Deliverables.**
- Migration `app/Domains/Comment/Database/Migrations/YYYY_MM_DD_HHMMSS_create_comment_annotations_table.php` matching the schema in [Architecture §2.1](./Chapter_Annotations_Architecture.md#21-comment_annotations-single-table-for-roots--replies).
- Indexes: `(comment_id, parent_annotation_id, deleted_at)`, `(parent_annotation_id)`.
- Model `App\Domains\Comment\Private\Models\CommentAnnotation` with `SoftDeletes`, Laravel 13 attribute syntax (`#[Table]`, `#[Fillable]`), `comment()`, `parent()`, `replies()`, `scopeRoots()`, `scopeReplies()`.
- Working `down()`.

**Tests.**
- PHP integration tests: create a root annotation, create a reply pointing at it, soft-delete the root, assert the reply still exists (cascade is via listeners, not the model — phase 5 covers cascades).
- `scopeRoots` / `scopeReplies` return the right rows.
- Round-trip with `processed_at` set on flip.

**Acceptance.**
- ✅ Migrations run and roll back cleanly.
- ✅ Model tests pass.
- ✅ Deptrac unaffected.

---

## Phase 5 — Policy contract + cascade listeners

**Goal.** Extend `CommentPolicy` with the annotation methods, override in `ChapterCommentPolicy`, wire up the four cascade listeners.

**Deliverables.**
- New methods on `CommentPolicy` interface (defaulted in `DefaultCommentPolicy`):
  - `isAnnotable(): bool` (default `false`)
  - `canAnnotate(int $entityId, int $userId): bool` (default `false`)
  - `getAnnotationBodyMinLength(): ?int` (default `1`)
  - `getAnnotationBodyMaxLength(): ?int` (default `1000`)
  - `getAnnotationHighlightedTextMaxLength(): ?int` (default `500`)
  - `canMarkAsProcessed(int $entityId, int $userId): bool` (default `false`)
- `CommentPolicyRegistry` gets matching pass-through methods.
- `ChapterCommentPolicy` overrides:
  - `isAnnotable` → `true`
  - `canAnnotate` → user is signed-in, not a co-author of the parent story (delegates to the same check used by `canCreateRoot` minus the "already posted root" rule, since post-publish add is vNext we'll keep the rule "user must be allowed to comment" — i.e. the existing `canCreateRoot` logic verbatim. Annotation creation is gated by also having a root comment to attach to, but at the wire we accept N annotations in the same transaction.)
  - `canMarkAsProcessed` → user is an author / co-author of the chapter's story
- Listeners under `app/Domains/Comment/Private/Listeners/`:
  - `CascadeAnnotationsOnCommentDeleted`
  - `CascadeAnnotationsOnUserDeleted`
  - `CascadeAnnotationsOnUserDeactivated`
  - `CascadeAnnotationsOnUserReactivated`
- Registered in `CommentServiceProvider`.

**Tests.**
- Policy unit tests for `ChapterCommentPolicy` overrides.
- Integration tests: create a root comment + annotations, soft-delete the root via the existing moderation flow → all annotations soft-deleted. Same for user deletion / deactivation / reactivation cycle.

**Acceptance.**
- ✅ Policy methods return the expected values per role.
- ✅ Cascade tests green.

---

## Phase 6 — PHP services + `AnnotationPublicApi`

**Goal.** Backend behaviour for v1: load, atomic create, set processed, moderator delete.

**Deliverables.**
- `AnnotationService` (private). Methods: `createForRootComment`, `setProcessed`, `moderatorDelete`, `getForEntityForViewer` (internal helper used by `AnnotationPublicApi`).
- `AnnotationAccessService` (private). Computes per-viewer permission flags on each row + filters out invisible annotations.
- `AnnotationPublicApi` (public) with v1 surface only:
  - `getForEntity(string $entityType, int $entityId, int $viewerId): AnnotationListDto`
  - `createForRootComment(int $commentId, int $authorId, array $annotations): array`
  - `setProcessed(int $annotationId, int $byUserId, bool $value): void`
  - `moderatorDelete(int $annotationId, int $byUserId): void`
- DTOs: `AnnotationDto`, `AnnotationListDto`, `AnnotationToCreateDto`. (No reply DTO — `AnnotationDto` carries `parentAnnotationId` and an empty `replies` array; replies are vNext.)
- Sanitizer profile `annotation` added to `CommentBodySanitizer`.

**Tests.**
- Unit tests for `AnnotationService` mocking the model / `CommentPublicApi`.
- Integration tests: a chapter author cannot annotate their own chapter; a non-confirmed user cannot annotate; visibility rules return correct rows per viewer role.
- Transaction tests: failing annotation validation inside `createForRootComment` rolls back the whole DB transaction (no root comment, no annotations).

**Acceptance.**
- ✅ All unit + integration tests green.
- ✅ `CommentPublicApi::getFor` is extended with `viewerAnnotationCount` per row (one COUNT() subquery, scoped to viewer visibility).
- ✅ Deptrac stays green.

---

## Phase 7 — Backend endpoints

**Goal.** HTTP routes + controllers wired to the public API. No UI yet.

**Deliverables.**
- Routes appended to `app/Domains/Comment/Private/routes.php`:
  - `GET  /comments/annotations?entity_type=&entity_id=` → `AnnotationListDto`
  - `POST /comments` extended to accept `annotations[]` (atomic with comment creation)
  - `POST /comments/annotations/{annotationId}/processed` → toggle
  - `DELETE /comments/annotations/{annotationId}` → moderator delete
- Form requests: `StoreAnnotationsWithCommentRequest` (extends the existing root-comment request), `SetAnnotationProcessedRequest`.
- Controllers: `AnnotationController` (`getForEntity`, `setProcessed`), `AnnotationModerationController` (`delete`). `CommentController::store` extended.
- Middleware per [Architecture §3.7](./Chapter_Annotations_Architecture.md#37-routes).

**Tests.**
- Feature tests:
  - Guest cannot GET (`auth` middleware enforced).
  - Unconfirmed user cannot POST (write middleware enforced).
  - Author of the chapter cannot create root comment + annotations on their own chapter.
  - Creating root comment + 3 annotations atomically: all four rows present, single transaction (test by forcing a failure in the annotation validation and asserting the comment row is absent).
  - Mark as processed by the chapter author succeeds; by a non-author fails 403.
  - Moderator delete succeeds; non-moderator fails 403.
  - GET returns only the viewer-visible subset.

**Acceptance.**
- ✅ All feature tests green.
- ✅ Manual `curl` smoke for the four endpoints.
- ✅ Existing comment tests still pass (regression).

---

## Phase 8 — Moderation topic registration

**Goal.** Register `chapter-annotation` as a moderation topic with a dedicated snapshot formatter and seeded reasons. No UI yet — the Report action is added in phase 12.

**Deliverables.**
- `AnnotationSnapshotFormatter` in `app/Domains/Comment/Private/Mappers/` implementing `SnapshotFormatterInterface`.
- Snapshot Blade view `app/Domains/Comment/Private/Resources/views/moderation/annotation-snapshot.blade.php` rendering: chapter title + link, commenter info, highlighted-text blockquote, annotation body, parent root-comment body excerpt.
- Lang files `app/Domains/Comment/Private/Resources/lang/{fr,en}/annotation_moderation.php` with `topic_name`, snapshot labels.
- Registration in `CommentServiceProvider::boot()`.
- Seeder for default reasons under the `chapter-annotation` topic — initial set (FR): `"Hors-sujet"`, `"Spoiler non balisé"`, `"Propos offensants"`, `"Spam"`, `"Autre"`. Implementation: extend the existing `ModerationSeeder` to include `chapter-annotation` in its topic list (the seeder already inserts an `"Autre"` reason per topic).

**Tests.**
- Integration test: submitting a report via `POST /moderation/report` with `topic_key=chapter-annotation` + a valid `entity_id` persists a `ModerationReport` row.
- Snapshot formatter renders without error for a sample annotation.

**Acceptance.**
- ✅ Topic visible in the moderation admin panel.
- ✅ Default reasons seeded.
- ✅ Snapshot view renders in the admin panel for a sample annotation.

---

## Phase 9 — JS pure core

**Goal.** The hard, regression-prone JS, all as pure functions, fully unit-tested. No DOM wiring yet.

**Deliverables.**
- `app/Domains/Comment/Resources/js/annotations/selection/canonical-text.js`
  - `buildCanonicalText(rootEl)` → `{ text, nodeMap }` per [Architecture §4.3](./Chapter_Annotations_Architecture.md#43-pure-functional-core).
  - Custom emoji blots replaced by `:{name}:`.
  - Block boundaries contribute a single space.
- `app/Domains/Comment/Resources/js/annotations/selection/extract-anchor.js`
  - `extractAnchor(range, rootEl, canonicalText)` → `{ highlighted, prefix, suffix }`.
  - Up to 5 words on each side; empty at chapter boundaries OK.
  - Returns `null` if the highlight exceeds 500 plain-text chars.
- `app/Domains/Comment/Resources/js/annotations/stores/drafts-store.js`
  - Pure reducer over the local-storage drafts shape ([Architecture §4.4](./Chapter_Annotations_Architecture.md#44-local-storage-schema)).
  - Methods: `load(key)`, `add(state, draft)`, `edit(state, tempId, body)`, `delete(state, tempId)`, `clear(key)`.

**Tests.**
- Vitest unit tests for every function. Test fixtures: small synthetic HTML strings parsed into a happy-dom `DocumentFragment`, then passed to the functions.
- `canonical-text`: HTML stripped, emojis turned into `:name:`, paragraph boundaries become spaces, node map round-trips a known offset back to the right text node + offset.
- `extract-anchor`: synthetic `Range` over a known node → expected `{prefix, highlighted, suffix}` for several positions (chapter start, chapter end, mid-paragraph, across a `<p>` boundary).
- `drafts-store`: add / edit / delete reduce correctly; `load` returns empty state when key missing; version mismatch discards.

**Acceptance.**
- ✅ Coverage of pure functions in this module > 90% line.
- ✅ All edge cases above explicitly tested.

---

## Phase 10 — `<x-comment::annotable>` bootstrap (invisible)

**Goal.** Wire the wrapper component, load annotations from the server, expose the Alpine store. Still nothing visible on the chapter.

**Deliverables.**
- Blade component `app/Domains/Comment/Public/View/Components/AnnotableComponent.php` + template `annotable.blade.php`. Props: `entityType`, `entityId`, `gutter` (default `true`; unused in v1).
- Renders `<div class="comment-annotable" data-annotable data-entity-type="..." data-entity-id="..." data-can-annotate="..." data-viewer-role="...">{slot}</div>`.
- Pushes the Vite include via `@once @push('scripts') @vite(...)`.
- JS bootstrap `app/Domains/Comment/Resources/js/annotations/index.js`:
  - Finds `[data-annotable]`. If more than one, logs and exits.
  - Builds canonical text. Fetches `GET /comments/annotations` for the entity. Populates `Alpine.store('annotations')` with the response.
- Story `chapters/show.blade.php` updated to wrap the chapter content in `<x-comment::annotable :entity-id="$chapter->id" entity-type="chapter">…</x-comment::annotable>`.

**Tests.**
- Vitest DOM test: render a minimal HTML fixture containing `[data-annotable]`, mock `fetch`, assert `Alpine.store('annotations').items` is populated.
- Manual smoke: open a chapter, open browser devtools, confirm the GET fires, the store is populated, no visible change to the page.

**Acceptance.**
- ✅ GET fires once per chapter view.
- ✅ Multiple `[data-annotable]` warning works.
- ✅ Existing chapter page passes manual smoke (nothing breaks).

---

## Phase 11 — Write mode

**Goal.** Highlight → "Annoter" → inline form → draft saved → banner appears. End-to-end, but nothing persisted server-side yet (publish wiring is phase 13).

**Deliverables.**
- Floating toolbar component (`toolbar.js` + a `<template>` in the annotable Blade). Appears on non-empty text selection inside the annotable container. Single button: **"Annoter"**.
- Inline form component (`inline-form.js` + `<template>` in the annotable Blade). Renders the minimal `<x-shared::editor :toolbar="['bold','italic','custom-emoji']">`, Save / Cancel buttons. Enforces 1 / 1000-char body, 500-char highlight (rejects at submit with inline error).
- On Save: pull selection, call `extractAnchor`, push to `drafts-store`, persist to local storage, close form.
- Banner component (`save-banner.js` + Blade) rendered above the root-comment editor, visible only when `drafts.length > 0`. Copy: *"{N} annotations, écrivez votre commentaire pour les sauvegarder"*. Includes a **"Voir les annotations"** button (wired in phase 12).

**Tests.**
- Vitest DOM tests via testing-library:
  - Synthetic `Range` over a fixture → toolbar appears.
  - Click "Annoter" → form renders.
  - Type body + click Save → store updates, `localStorage` set, form closed.
  - Cancel → no state change.
  - Banner shows when `drafts.length > 0`, hides at 0.
- Manual smoke: highlight a passage on a chapter, "Annoter", type, save. Reload page; banner still shows 1. Refresh root-comment editor; the banner still shows 1.

**Acceptance.**
- ✅ Drafts survive reload, scoped by user + chapter.
- ✅ No console errors.
- ✅ Manual smoke green on Chrome and Firefox (desktop) and mobile Safari (touch selection — verify the toolbar appears).

---

## Phase 12 — Pop-up modal (drafts mode + server mode)

**Goal.** One Alpine modal component, two data sources, three viewer-action sets. This is the heaviest UI piece in v1.

**Deliverables.**
- Modal component `modal.js` + Blade partial. Uses `<x-shared::modal>`.
- **Drafts mode** (triggered by the banner's "Voir les annotations" button):
  - Reads from `drafts-store`.
  - Each row: blockquote of `highlighted`, rendered body, **Edit** (opens body in the same minimal editor inline), **Delete** (immediate, mutates store + local storage).
- **Server mode** (triggered by the "N annotations" button on a root-comment row in `<x-comment::comment-list>`):
  - Fetches `GET /comments/annotations`, filters to the commenter for the clicked row.
  - Per-row actions per viewer:
    - **Commenter** on own row: read-only.
    - **Chapter author / co-author**: **Mark/Unmark as processed** (immediate AJAX), **Report** (opens the existing moderation report modal pointed at `topic_key=chapter-annotation`, `entity_id=annotationId`).
    - **Moderator**: **Delete annotation** (immediate AJAX).
- `<x-comment::comment-list>` modified: when `viewerAnnotationCount > 0`, render an "N annotations" button between the comment header and body.

**Tests.**
- Vitest DOM tests via testing-library:
  - Drafts mode: open modal, Edit → store updated, Delete → store updated.
  - Server mode: mock fetch with three fixtures (commenter / author / moderator viewerRole), assert per-row actions render accordingly.
  - Clicking Mark as processed fires `POST /comments/annotations/{id}/processed` with the right payload.
- PHP feature tests: the "N annotations" button appears only when `viewerAnnotationCount > 0` for the viewer.
- Manual smoke: each of the three roles (commenter, chapter author, moderator) interacts with the modal end-to-end on a chapter with multiple commenters.

**Acceptance.**
- ✅ Reports submitted from the modal land in the moderation admin panel under the `chapter-annotation` topic with the snapshot rendering correctly.
- ✅ Mark-as-processed reflects without refresh.
- ✅ Moderator delete removes the row from the modal instantly.
- ✅ Closing and reopening the modal does not double-fetch (small caching reasonableness).

---

## Phase 13 — Atomic publish wiring

**Goal.** The root-comment submit collects drafts and posts them with the comment. Drafts cleared on success.

**Deliverables.**
- Hook into the existing root-comment form submit (in `comment-list.blade.php`): before submit, read `drafts-store`, set a hidden `annotations[]` payload, then submit. On 2xx response, clear drafts local storage.
- Handle 4xx: surface the validation error inline (the form already does this for the body; same UX for annotation-array errors).

**Tests.**
- Vitest DOM test: with drafts in store, submit the form (mocked endpoint), assert payload includes the drafts and drafts are cleared after success.
- End-to-end manual smoke: highlight, annoter, comment, submit. After submit, the chapter page reloads (existing behaviour). The drafts banner is gone. The root-comment row in the comment list shows the "N annotations" button. Clicking it opens the modal in server mode listing the just-published annotations.

**Acceptance.**
- ✅ Round-trip: client → server → DB → server response → client UI all consistent.
- ✅ Submitting a comment with **zero** drafts still works exactly as today.
- ✅ A backend-side annotation validation failure shows an error to the user and **does not** clear drafts (the user can fix and resubmit).

---

## Phase 14 — v1 polish

**Goal.** Translate, make accessible, smoke-test broadly. Last phase before declaring v1 done.

**Deliverables.**
- Lang files updated FR + EN for: banner copy, toolbar label, modal title, processed/delete confirmations, report-modal labels, error messages.
- Keyboard accessibility on the modal: focus trap, `Esc` closes, tab order sane.
- Keyboard shortcut: `Ctrl/Cmd+Enter` saves the inline annotation form.
- Aria labels on the toolbar, the banner button, the modal close, the per-row action buttons.
- Manual QA checklist filled (a Markdown table in this doc, see end).
- Performance smoke: a chapter with 50 published annotations, page load + modal open both under 1 s.

**Tests.**
- Vitest: a few testing-library a11y assertions (focus trap, esc closes).
- Manual QA checklist (below) all green.

**Acceptance.**
- ✅ Manual QA checklist 100%.
- ✅ Lighthouse a11y score on the chapter page does not regress.

---

## Manual QA checklist (filled during phase 14)

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page (commenter, before annotating) | Loads as before, no extra network calls beyond `GET /comments/annotations` once | |
| Chapter page (commenter, no published annotations) | Banner hidden, no "N annotations" button on any comment | |
| Write — single annotation | Highlight, annoter, type 5 chars, save → banner shows "1 annotation" | |
| Write — body too long | Highlight, annoter, type 1001 chars → inline error, no save | |
| Write — highlight too long | Highlight 501 chars → toolbar disabled or error | |
| Write — overlapping highlights | Same user can annotate the same passage twice → both saved | |
| Drafts modal — Edit | Open modal, edit body, save → updated | |
| Drafts modal — Delete | Delete a draft → banner count decreases | |
| Atomic publish — success | Submit comment with drafts → drafts cleared, modal in server mode shows them | |
| Atomic publish — validation failure | Force a backend error → drafts preserved, error displayed | |
| Server mode (commenter view) | Own row only, read-only actions | |
| Server mode (author view) | Sees all commenters, Mark-as-processed flips the badge | |
| Server mode (moderator view) | Delete removes the row immediately | |
| Report flow | Report → form opens with `chapter-annotation` reasons → submit → report appears in admin panel | |
| Moderation cascade | Moderator deletes the root comment → modal no longer shows the "N annotations" button on it | |
| Mobile (iOS Safari) | Touch-selection surfaces the toolbar; tap "Annoter"; type; save; banner shows | |
| Mobile (Android Chrome) | Same | |
| Auth — guest | No toolbar, no banner, no "N annotations" buttons | |
| Auth — unconfirmed | Same as guest (write middleware blocks even if UI shows) | |
| Performance | 50 annotations: GET < 500 ms, modal open < 300 ms | |

---

## Open items (none blocking)

- (Optional) Phase 8's seeded reason list. The proposed default set is FR-leaning; we may want EN equivalents too. Easy to adjust in the seeder.
- (Optional) Whether `compliant` and `role:user-confirmed` are redundant on annotation routes. We'll verify in phase 7 and trim if so.

---

## After v1

The post-v1 roadmap (post-publish editing, replies, gutter, filter, …) is sketched in [Architecture §8.2](./Chapter_Annotations_Architecture.md#82-post-v1-roadmap-not-committed). We won't plan those phases concretely until v1 ships and we see usage.
