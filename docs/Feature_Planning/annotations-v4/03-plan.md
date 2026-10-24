# Chapter annotations — v4 (per-annotation Report) — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Moderation — optional report guard contract (403 on refusal) | S | — | TODO |
| 2 | Comment — `annotation` moderation topic: access rule, guarded formatter, snapshot, seeded reason | M | 1 | TODO |
| 3 | Comment — `can_report` display hint on `AnnotationDto` | S | 2 | TODO |
| 4 | UI — « Signaler » in the « N annotations » pop-up | S | 3 | TODO |
| 5 | UI — « Signaler » in the in-chapter popover, plus e2e spec | S | 3 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

No checkpoint row. Phase 1 is the only change to code with existing consumers
(every report topic goes through `ModerationService::createReport`), but it is
purely additive: a topic whose formatter does not implement the new contract
takes exactly today's path, the JS is untouched, and the existing
`SubmitModerationReportTest` / `FanartReportTest` pin that behaviour at the
endpoint. A browser checkpoint would see nothing those tests do not.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Settled points live in `DECISIONS.md` (#1–#7, A1–A10). Do not re-ask them.

---

## Phase 1 — Moderation: optional report guard contract

**Goal.** Let a topic's snapshot formatter refuse a report for a given
(entity, reporter), with the refusal surfacing as HTTP 403 and no report row;
topics that do not opt in are unchanged.

Architecture: §1.1 (Moderation bullet), §3.1 (Moderation contract), §3.3.

**Deliverables.**
- `app/Domains/Moderation/Public/Contracts/ReportGuardInterface.php` (new):
  ```php
  interface ReportGuardInterface
  {
      /** False refuses the report; called before any snapshot is captured. */
      public function canReport(int $entityId, int $reporterId): bool;
  }
  ```
  Docblock: optional, implemented by a formatter alongside
  `SnapshotFormatterInterface`.
- `app/Domains/Moderation/Private/Services/ModerationService.php` —
  `createReport`: inside the existing `hasFormatter` branch, **before**
  `getReportedUserId` / `capture`, if the formatter `instanceof
  ReportGuardInterface` and `! $formatter->canReport($entityId, (int) Auth::id())`,
  throw `Illuminate\Auth\Access\AuthorizationException`. Add the `@throws` to the
  docblock.
- `app/Domains/Moderation/Private/Controllers/ModerationReportController.php` —
  `store`: a `catch (AuthorizationException $e)` placed **before** the generic
  `\Exception` catch, returning `['success' => false, 'message' =>
  __('moderation::report.error')]` with status 403. (Note: `AuthorizationException`
  is not an `InvalidArgumentException`, so catch order between those two is free;
  it must precede `\Exception`.)
- `app/Domains/Moderation/README.md` and `app/Domains/Moderation/AGENTS.md` —
  document the optional `ReportGuardInterface` extension point (what it refuses,
  when it is called, 403 mapping). No link to `docs/Feature_Planning`.

**Tests.**
- `app/Domains/Moderation/Tests/Feature/ReportGuardTest.php` (new). Declares two
  test formatter classes in the file (one implementing both interfaces whose
  `canReport` returns `$entityId !== 13`, recording that `capture` was not
  called; one implementing only `SnapshotFormatterInterface`), registers them on
  the `ModerationRegistry` singleton under test-only keys in `beforeEach`, and
  uses `createReason()` from `Moderation/Tests/helpers.php`:
  - `it('refuses a report the topic guard rejects with 403 and stores nothing')`
  - `it('does not capture a snapshot when the guard refuses')`
  - `it('passes the authenticated reporter id to the guard')`
  - `it('accepts a report the topic guard allows')`
  - `it('accepts reports on a topic whose formatter has no guard')`
- Existing `SubmitModerationReportTest`, `ReportMediaSnapshotTest`,
  `Fanart/Tests/Feature/FanartReportTest.php` stay green unchanged.

**Acceptance.**
- ✅ POST `/moderation/report` on a guarded topic with a refused id returns 403,
  JSON `success: false`, and `moderation_reports` stays empty.
- ✅ The same request with an allowed id returns 200 and creates the row.
- ✅ Topics without the contract (`comment`, `fanart`, `profile`…) behave as before.
- ✅ No JS change, no new route, no new deptrac edge.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Comment: `annotation` moderation topic

**Goal.** Register a moderation topic `annotation` (entity id =
`comment_annotations.id`, roots and replies alike) whose formatter refuses
reports from people who cannot see the annotation or who wrote it, and freezes
passage + body (+ parent for a reply) at report time.

Builds on phase 1: `App\Domains\Moderation\Public\Contracts\ReportGuardInterface`
exists (`canReport(int $entityId, int $reporterId): bool`); when a registered
formatter implements it, `ModerationService::createReport` calls it before
`capture` and a `false` makes POST `/moderation/report` answer 403 with no row.

Architecture: §1, §2.3, §3.2, §3.3; spec §4.1.6, §4.2, §5.

**Deliverables.**
- `app/Domains/Comment/Private/Services/AnnotationAccessService.php` — the one
  report rule, two entry points (§3.2):
  - `private function isWriter(CommentAnnotation $a, int $viewerId): bool` —
    `author_id !== null && (int) author_id === $viewerId` (an anonymised
    annotation has no writer, so it stays reportable).
  - `public function canReport(string $viewerRole, CommentAnnotation $annotation, int $viewerId): bool`
    — UI hint, for rows the read path already filtered: `! isWriter && !
    $annotation->isEmptied()`. (Used in phase 3; the role argument is kept so
    the signature matches the other `can*` helpers and stays open to a
    role-specific rule.)
  - `public function canReportById(int $annotationId, int $viewerId): bool` —
    server guard. In order, `false` as soon as one fails:
    1. the annotation exists and is not soft-deleted (`CommentAnnotation::find`);
    2. for a reply, its parent root exists and is not soft-deleted; the root is
       the annotation itself otherwise;
    3. the root comment exists and is not trashed (`Comment::find($a->comment_id)`,
       default soft-delete scope);
    4. `resolveViewerRole($comment, $viewerId)` is not null;
    5. if the role is `commenter`, the root annotation's `author_id` is the
       viewer (same restriction as `restrictToAuthorId`);
    6. for a reply, `filterActiveReplyWriters(collect([$a]))` keeps it
       (a deactivated writer's reply is unreportable, as it is unreadable);
    7. `! isWriter($a, $viewerId)`.
    Emptied annotations are **not** refused here (DECISIONS A10).
- `app/Domains/Comment/Private/Support/Moderation/AnnotationSnapshotFormatter.php`
  (new), `implements SnapshotFormatterInterface, ReportGuardInterface`, injected
  with `CommentPolicyRegistry` and `AnnotationAccessService`:
  - `capture(int $entityId): array` — `[]` when the row is gone (`find`, soft
    deletes excluded); else `['highlighted_text' => …, 'body' => …]`, plus for a
    reply `'parent' => ['highlighted_text' => …, 'body' => …]` read with
    `withTrashed()` (empty strings if the parent row is hard-gone).
  - `render(array $snapshot): string` —
    `view('comment::moderation.annotation-snapshot', …)`.
  - `getReportedUserId(int $entityId): int` — `author_id`, `0` when null or row gone.
  - `getContentUrl(int $entityId): string` — loads the annotation then its
    comment `withTrashed()`, returns
    `$policyRegistry->getUrl(commentable_type, commentable_id, comment_id) ?? '/'`;
    `/` when either row is gone.
  - `canReport(int $entityId, int $reporterId): bool` — delegates to
    `AnnotationAccessService::canReportById`.
- `app/Domains/Comment/Private/Resources/views/moderation/annotation-snapshot.blade.php`
  (new), sibling of `comment-snapshot.blade.php`: labelled passage rendered as
  **text** (`{{ }}` — `highlighted_text` is stored unsanitized), body with
  `{!! !!}` (purified at write time, as the comment snapshot does), and when
  `parent` is set a "En réponse à" block with the parent's passage (text) and
  body. Empty snapshot renders without error.
- `app/Domains/Comment/Private/Resources/lang/fr/moderation.php` — new keys:
  `annotation_topic_name` « Annotation », `annotation_passage` « Passage annoté : »,
  `annotation_body` « Annotation : », `annotation_parent` « En réponse à : ».
- `app/Domains/Comment/Public/Providers/CommentServiceProvider.php` — `boot()`:
  second `ModerationRegistry::register(key: 'annotation', displayName:
  __('comment::moderation.annotation_topic_name'), formatterClass:
  AnnotationSnapshotFormatter::class)`, next to the `comment` one.
- `app/Domains/Moderation/Database/Seeders/ModerationSeeder.php` — add
  `'annotation'` to `$topics` (gets the standard `Other` reason, DECISIONS A9).
- Docs: `app/Domains/Comment/AGENTS.md` (Registry integrations: second topic
  `annotation`; invariant: the report guard and the future `can_report` hint
  share `AnnotationAccessService`'s rule, emptied annotations are allowed
  server-side) and `app/Domains/Comment/README.md` § Moderation integration.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/ReportAnnotationTest.php` (new).
  Scene like `AnnotationRepliesTest`: author `alice`, co-author `carol` via
  `addCollaborator`, reader `bob` posts an annotated root comment on a published
  chapter through `annotatedChapterCommentPayload`; replies and extra rows via
  `CommentAnnotation::forceCreate`; reason via `defaultReason('annotation')` (or
  `createReason`); requests are `postJson('/moderation/report', …)`.
  Accepted (200, one row, `topic_key = 'annotation'`, `entity_id` = annotation id):
  - `it('lets the commenter report an author reply on their annotation')`
  - `it('lets an author report a reader root annotation')`
  - `it('lets an author report a reader reply')`
  - `it('lets a co-author report another co-author reply')`
  - `it('lets a moderator report a root annotation and a reply')`
  - `it('lets a non-confirmed user who is a party to the thread report')` — a
    `user`-role commenter (role via `alice(..., roles: [Roles::USER])`); seed the
    comment/annotation rows directly if a `user` cannot post on the chapter.
  - `it('accepts a report on an emptied root annotation')` — snapshot body is the
    `annotations.emptied_body` placeholder.
  - `it('accepts duplicate reports by the same user')`
  Refused (403, `moderation_reports` empty):
  - `it('refuses a user unrelated to the thread')`
  - `it('refuses the writer of the root annotation')`
  - `it('refuses the writer of the reply')`
  - `it('refuses a non-existent annotation id')`
  - `it('refuses a commenter on another reader root comment of the same chapter')`
  - `it('refuses when the root comment is trashed')`
  - `it('refuses a soft-deleted annotation and a reply under a soft-deleted root')`
  - `it('refuses a reply whose writer is deactivated')`
  Snapshot and metadata:
  - `it('freezes passage and body for a root annotation')`
  - `it('freezes the reply body and its parent passage and body')`
  - `it('sets reported_user_id to the annotation writer')`
  - `it('links the report to the root comment URL')` — equals
    `app(CommentPolicyRegistry::class)->getUrl('chapter', $chapter->id, $comment->id)`.
  - `it('keeps the report and its snapshot after the annotation is deleted')` —
    report, then `AnnotationPublicApi::moderatorDelete`; row and snapshot intact,
    and a moderator sees the passage on the admin reports page (route as used in
    `Moderation/Tests/Feature/Admin/ModerationAdminControllerTest.php`).
  - `it('seeds an Other reason for the annotation topic')` —
    `$this->seed(ModerationSeeder::class)` (pattern from `FanartReportTest`).
- `app/Domains/Comment/Tests/Unit/AnnotationSnapshotFormatterTest.php` (new,
  same style as `CommentSnapshotFormatterTest`):
  - `it('captures an empty array for a missing annotation')`
  - `it('returns 0 as reported user and / as url for a missing annotation')`
  - `it('renders the passage as escaped text and the parent block for a reply')`
  - `it('renders an empty snapshot without error')`

**Acceptance.**
- ✅ A user unrelated to the thread gets 403 on POST `/moderation/report` with
  `topic_key=annotation` and any existing annotation id; no row is created and
  nothing of the annotation is captured.
- ✅ The writer of an annotation or reply gets 403 reporting it.
- ✅ A non-existent id gets 403.
- ✅ Commenter, author, co-author, moderator, `user` and `user-confirmed` get 200
  in the permitted cases above.
- ✅ Snapshot holds `highlighted_text` + `body`, and `parent` for a reply; it
  survives deletion of the annotation.
- ✅ `content_url` is the root comment URL on the chapter.
- ✅ `ModerationSeeder` creates the `Other` reason for `annotation`; the topic
  shows as « Annotation » in the reasons admin.
- ✅ `pnpm run gate` green.

---

## Phase 3 — Comment: `can_report` display hint on `AnnotationDto`

**Goal.** Every annotation and reply DTO returned to a viewer carries
`can_report`, computed by the same rule as the server guard, so the UI can show
« Signaler » without duplicating the rule in JS.

Builds on phase 2: `AnnotationAccessService::canReport(string $viewerRole,
CommentAnnotation $annotation, int $viewerId): bool` exists (true unless the
viewer wrote it or it is emptied) and the server guard for topic `annotation`
is live.

Architecture: §3.1 (Comment bullet), §3.2, §7 #2.

**Deliverables.**
- `app/Domains/Comment/Public/Api/Contracts/AnnotationDto.php` — new constructor
  parameter `public readonly bool $canReport = false` (after `isEmptied`, so
  existing named-argument call sites keep compiling), docblock line "Display
  hint: the viewer may report this row; re-checked server-side", and
  `'can_report' => $this->canReport` in `toArray()`.
- `app/Domains/Comment/Public/Api/AnnotationPublicApi.php`:
  - `buildItems` — pass `canReport: $this->access->canReport($role, $a, $viewerId)`
    for each root;
  - `toReplyDto` — gain a `bool $canReport` argument, computed in `buildItems`
    with the same call on the reply.
  Both `getForComment` and `getForEntity` go through `buildItems`, so both
  payloads carry the flag. Check any other `new AnnotationDto(` call site
  (e.g. `moderatorEmpty`'s refreshed DTO, `reply`'s return) and pass the flag
  where a viewer is known; elsewhere the default `false` is correct only if the
  DTO is not rendered with a report button — note the choice in the commit.
- `app/Domains/Comment/AGENTS.md` — add `can_report` to the list of display hints
  in "Annotation replies are one level deep and role-gated".

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/AnnotationCanReportFlagTest.php` (new):
  - `it('flags reader annotations reportable for an author, not their own replies')`
    — `GET /comments/{id}/annotations` as author.
  - `it('flags author replies reportable for the commenter, not their own roots')`
  - `it('flags everything reportable for a moderator except emptied roots')`
  - `it('does not flag an emptied root annotation')`
  - `it('flags a reaction like any annotation')` — a root whose body is a single emoji.
  - `it('carries can_report on the in-entity payload')` — `GET
    /comments/annotations/in-entity` (see `AnnotationsInEntityTest` for the
    query string) as author and as commenter.
- Existing `GetAnnotationsEndpointTest`, `AnnotationsInEntityTest`,
  `AnnotationRepliesTest`, `EmptyAnnotationContentTest` stay green; adjust only
  exact-shape assertions that list every DTO key.

**Acceptance.**
- ✅ Every root and reply in both payloads has a boolean `can_report`.
- ✅ `can_report` is false on the viewer's own rows and on emptied roots, true
  otherwise, for commenter, author and moderator.
- ✅ No JS change.
- ✅ `pnpm run gate` green.

---

## Phase 4 — UI: « Signaler » in the « N annotations » pop-up

**Goal.** In the per-commenter pop-up under a root comment, show a « Signaler »
button on every root and reply whose `can_report` is true, opening the existing
moderation report modal for topic `annotation`.

Builds on phases 2–3: topic `annotation` is registered with a server guard, and
every row (`row`) and reply (`reply`) from `GET /comments/{id}/annotations`
carries `can_report`. The global `window.reportButton(topicKey, entityId)`
(Moderation `moderation.js`, imported by Shared `app.js`) fetches
`/moderation/report-form/{topic}/{id}`, injects the modal into its
`x-ref="modalContainer"` and initialises it.

Architecture: §4, §7 #3.

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/partials/annotation-modal.blade.php`:
  - in each root's action row (beside the `canEdit` / `canToggle` /
    `canEmpty` / `canRemove` templates), a `<template x-if="row.can_report">`
    wrapping `<div x-data="reportButton('annotation', row.id)">` with a
    `<x-shared::button type="button" size="sm" color="error" :outline="true"
    x-on:click="loadForm()" x-bind:disabled="loading">` (flag icon + label
    `__('moderation::report.button')`) and `<div x-ref="modalContainer"></div>`;
  - the same, bound to `reply.id` and `reply.can_report`, in the reply list
    (beside `canDeleteReply(reply)`).
  Rows that exist only client-side (pending adds) have no `can_report`, so they
  show nothing. If the action row is wrapped in an `x-show` listing the other
  actions, add `|| row.can_report` to it.
  Each button carries `data-testid="annotation-report"` (pop-up) for the e2e
  spec in phase 5.
- No JS change (`modal.js`, `api.js` untouched).

**Tests.**
- Blade render test, in the existing view test that renders the pop-up partial
  (find it with `grep -rl "annotation-modal" app/Domains/Comment/Tests`; create
  `app/Domains/Comment/Tests/Feature/Views/AnnotationModalReportButtonTest.php`
  if none):
  - `it('renders the report button template bound to row and reply can_report')`
    — asserts `reportButton('annotation', row.id)`,
    `reportButton('annotation', reply.id)`, `row.can_report`, `reply.can_report`.
- Vitest: none (template-only, §6).

**Acceptance.**
- ✅ A reader who opens the pop-up on their own root comment sees « Signaler » on
  the authors' replies only.
- ✅ An author sees it on the reader's roots and replies and on a co-author's
  reply, not on their own reply.
- ✅ A moderator sees it on every non-emptied row.
- ✅ Clicking it opens the reason select + description modal; submitting shows
  the standard confirmation.
- ✅ `pnpm run gate` green.

---

## Phase 5 — UI: « Signaler » in the in-chapter popover, plus e2e spec

**Goal.** In the v3 in-chapter popover (`md+`), show a compact « Signaler » on
the current annotation and on each of its replies when `can_report` is true,
and cover both surfaces with a Playwright spec.

Builds on phases 2–4: topic `annotation` is guarded server-side; every
annotation (`current`) and reply from `GET /comments/annotations/in-entity`
carries `can_report`; the pop-up already shows « Signaler » via an inline
`x-data="reportButton('annotation', <id>)"` + `x-ref="modalContainer"` block
with `data-testid="annotation-report"`.

Architecture: §4 (including its "Known risk" bullet), §6 VERIFY line.

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/annotation-overlay.blade.php`:
  - in the action row under `<template x-if="current">`, a
    `<template x-if="current.can_report">` with the same `reportButton` block,
    **compact**: flag icon only, `title` and `aria-label` =
    `__('moderation::report.button')`; add `|| current.can_report` to that row's
    `x-show` condition (today `canReply… || canToggle… || canDelete…`);
  - in each `<li data-testid="annotation-popover-reply">`, the same compact
    block bound to `reply.id` / `reply.can_report`.
  - `data-testid="annotation-popover-report"` on these buttons.
  - If the popover's outside-click handler closes it when the user clicks into
    the injected report modal (the modal is rendered inside the popover's DOM
    but displayed as a page overlay), make the handler ignore clicks inside the
    report modal container. Fix only if observed; record what was done.
- `e2e/tests/core/chapter-annotation-report.spec.ts` (new), reusing the
  fixtures/helpers of `chapter-annotation-round-trip.spec.ts`:
  - reader annotates, author replies; as the author, report the reader's
    annotation from the pop-up → confirmation shown;
  - as the reader, report the author's reply from the in-chapter popover →
    confirmation shown, popover state sane after closing the modal;
  - no « Signaler » on the viewer's own annotation/reply.
- `app/Domains/Comment/AGENTS.md` — Tests line: add the new e2e spec.

**Tests.**
- `app/Domains/Comment/Tests/Feature/Annotations/AnnotationOverlayComponentTest.php`
  — `it('renders the compact report button bound to current and reply can_report')`.
- The e2e spec above, run with `pnpm run e2e` (needs the app running; see
  `e2e/README.md`). The gate type-checks it.

**Acceptance.**
- ✅ On `md+`, the popover shows the flag icon on reportable rows only, with an
  accessible name « Signaler ».
- ✅ Clicking it opens the report modal above the popover; the modal is usable
  (focus, typing, submit) and closing it does not leave the page in a broken
  state.
- ✅ `pnpm run e2e -- chapter-annotation-report` passes.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Pop-up, author view (desktop) | « Signaler » on the reader's root annotations, on reactions, on a co-author's reply; absent on the author's own reply | |
| Pop-up, commenter view (desktop) | « Signaler » on the authors' replies only; absent on own roots, own replies and pending (unsaved) rows | |
| Pop-up, moderator view | « Signaler » on every row except an emptied root; « Supprimer » / « Vider le contenu » still present and aligned | |
| Pop-up, `user` (non-confirmed) commenter | Same as commenter view | |
| Pop-up, mobile width | Button wraps cleanly in the action row of roots and replies; report modal usable on a phone width | |
| Report modal from the pop-up | Opens above the pop-up (stacking), focus lands in it, Esc/close returns to the pop-up, submit shows the confirmation | |
| In-chapter popover (`md+`), author and commenter | Compact flag icon on the current annotation and on reportable replies, tooltip « Signaler »; absent on own rows | |
| Report modal from the popover | Opens above the popover; clicking inside it does not close the popover underneath; submit confirms; popover still navigable afterwards | |
| Popover on mobile | Popover absent below `md`, as in v3 (nothing new) | |
| Admin reports list / detail | Topic « Annotation »; snapshot shows passage and body; a reply also shows « En réponse à » with the parent; « Ouvrir » lands on the root comment anchor | |
| Admin, annotation deleted after report | Report still listed with its frozen snapshot; « Ouvrir » leads to a comment with nothing left or a missing one, without error | |
| Admin, emptied annotation reported via API | Snapshot shows the emptied placeholder body (button was hidden in UI) | |
| Admin reasons page | Topic « Annotation » selectable, with its « Other » reason after seeding | |

## Open items

- **Production reason (phase 2).** `ModerationSeeder` is the dev seeder
  (`Fanart/AGENTS.md`: "Reasons are admin-created; `ModerationSeeder` seeds an
  « Other » for dev"). Unless the seeder is run on production, the `annotation`
  topic ships with **no reason** there and the report modal has nothing to
  select until a moderator creates one. Spec §4.3 says the topic "ships with a
  single seeded reason". Not re-opening DECISIONS A9; confirm at WRAP whether
  the deploy runs the seeder, or a moderator creates « Autre » after release.
- **403 handling in the report modal JS (phase 4/5).** `reportForm.submitReport`
  in `Moderation/Private/Resources/js/moderation.js` redirects to `/login` on any
  401/403. A guard refusal can only reach it if the `can_report` hint and the
  guard disagree (e.g. the root comment was trashed while the pop-up was open),
  so the user would be sent to the login page rather than shown an error.
  Architecture §1.1 says no JS change; left as is, flagged for VERIFY/WRAP.
- **`user`-role commenter fixture (phase 2).** Not verified whether a
  non-confirmed `user` can post an annotated comment on a chapter through the
  endpoint; the test may need to seed the comment and annotation rows directly.
- **Duplicate modal names (phase 5).** The report modal is named
  `report-modal-annotation-<id>`; if the same annotation's report form is opened
  from both the pop-up and the popover on one page, two modals share a name.
  Check during the phase 5 e2e run; harmless if only the last injected opens.
