# Chapter annotations — v4 (per-annotation Report) — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Comment** owns the feature: the annotation rows (`comment_annotations`), their
visibility rules (`AnnotationAccessService`) and both UI surfaces live there. It
registers a second moderation topic, `annotation`, next to its existing
`comment` topic, with the same mechanism (`ModerationRegistry::register` from
`CommentServiceProvider::boot`, a `SnapshotFormatterInterface` implementation).
No new table, no new domain, no new deptrac edge.

The reported entity id is the `comment_annotations.id`; roots and replies share
that table, so one topic covers both.

### 1.1 Changes in other domains

- **Moderation** — one new **optional extension point**: a contract a topic's
  formatter may implement to refuse a report. `ModerationService::createReport`
  consults it before capturing; `ModerationReportController::store` maps the
  refusal to a 403. Topics that do not implement it behave exactly as today.
  The seeder gains the `annotation` topic so it gets its default « Other »
  reason (same label the other topics get — the spec's « Autre » is the
  French reading of that seeded row; see §7 #4).
- **Shared / Moderation JS** — none. The existing `reportButton(topicKey,
  entityId)` Alpine entry and the server-rendered report modal are reused as is.

## 2. Data model

### 2.1 Tables

None. Reports are `moderation_reports` rows (`topic_key = 'annotation'`,
`entity_id` = annotation id) with the frozen snapshot in the existing JSON
column. Reports reference the annotation by id without a foreign key (different
domains), so they outlive it (spec §5) with no cascade work.

### 2.2 Model

No change to `CommentAnnotation`.

### 2.3 Lifecycle rules

Nothing to cascade. A report survives deletion/emptying of its annotation, root
comment, chapter or story because the snapshot is self-contained. The formatter
tolerates a missing row (see 3.2).

## 3. PHP architecture

### 3.1 Public API

- **Moderation (new contract)**, `Moderation/Public/Contracts`:
  `ReportGuardInterface { public function canReport(int $entityId, int $reporterId): bool; }`.
  Optional: a formatter implements it alongside `SnapshotFormatterInterface`.
- **Comment**, `AnnotationDto` gains `canReport: bool` (serialised as
  `can_report`), a display hint like `canDelete`, set by the same code path that
  builds the rest of the DTO (`buildItems` / `toReplyDto`). Root and reply DTOs
  both carry it.

No other public surface changes. The new topic is not exposed by a public API;
other domains never call it.

### 3.2 Services

- **`AnnotationAccessService`** gains the single rule "may this viewer report
  this annotation": `canReport(role, annotation, viewerId)` for the DTO flag,
  and the guard's entity-id variant (loads the annotation + its comment, resolves
  the viewer role, applies the same rule). One rule, two entry points, so the UI
  hint and the server check cannot drift. The rule, from spec §3/§4.1:
  - the annotation exists, is not soft-deleted, and its root comment is not
    trashed;
  - the viewer has a role on the root comment (`resolveViewerRole` not null).
    A `commenter` additionally only sees their own roots and the replies under
    them (as `getForComment` already restricts); a deactivated reply writer is
    not reportable (matches the read-time filter);
  - the viewer is not the annotation's writer;
  - the UI hint is also false for an emptied root (A3). The server guard does
    **not** refuse emptied ones — spec §4.1.6 lists three refusals only; the
    snapshot then simply holds the placeholder.
- **`AnnotationSnapshotFormatter`** (new, `Comment/Private/Support/Moderation/`,
  beside `CommentSnapshotFormatter`), implements both contracts:
  - `capture`: `{ highlighted_text, body }` of the annotation; for a reply also
    `parent: { highlighted_text, body }`. Empty array when the row is gone,
    like the comment formatter.
  - `render`: a Blade partial in Comment's views, sibling of the comment
    snapshot partial.
  - `getReportedUserId`: the annotation's `author_id`, `0` when anonymised —
    same convention as the comment formatter.
  - `getContentUrl`: the **root comment** URL via
    `CommentPolicyRegistry::getUrl(commentable_type, commentable_id, commentId)`
    (decision #5); `/` when the row is gone.
  - `canReport`: delegates to `AnnotationAccessService`.

### 3.3 Policy / authorization

Enforcement point = `ReportGuardInterface::canReport`, called by
`ModerationService::createReport` after the topic/reason validation and before
`capture`. A `false` throws `Illuminate\Auth\Access\AuthorizationException`.
The controller adds a dedicated catch (before the generic `\Exception` → 500)
returning the standard JSON failure shape with HTTP 403. Role gate stays the
report route's own (`user`, `user-confirmed`, A6). The `GET report-form` endpoint
is not guarded: it renders reasons only and exposes nothing of the annotation.

### 3.4 Events and listeners

None new. `ReportSubmitted` fires from Moderation as for every topic.

### 3.5 Routes, controllers, form requests

No new route. Only the two Moderation edits above (service guard call,
controller catch). No `PATCH`.

## 4. Frontend architecture

Both surfaces are Blade templates driven by Alpine `x-for` (pop-up
`annotation-modal.blade.php`, popover `annotation-overlay.blade.php`), so the
button is added there, not through the PHP `<x-moderation::report-button>`
component (its entity id is rendered server-side, ours is a per-row JS value).

- A « Signaler » button, shown by `x-if="row.can_report"` (roots and replies),
  inside the same nested `x-data="reportButton('annotation', <id>)"` the Blade
  component uses, with the id bound from the row/reply. Reuses
  `window.reportButton` and the server-rendered report modal unchanged.
- Placement: the action row of a root and of each reply, in the pop-up; the
  popover's action row and its reply list. Compact (icon + title) in the
  popover.
- No store or API change in JS beyond reading `can_report` (the DTO is already
  passed through untouched by `api.js` and the in-chapter store).
- Known risk for VERIFY: the report modal is injected into a container inside an
  already-open modal (pop-up) and inside the popover, so stacking/focus and the
  popover's outside-click-closes behaviour must be checked in the browser.
- Lang (French): reuses `moderation::report.button`; new keys only for the
  topic name (`comment::moderation.annotation_topic_name` « Annotation ») and the
  snapshot partial labels.

## 5. Deptrac

No new edge. `CommentPrivate → ModerationPublic` already exists (the `comment`
formatter and the registry call); `CommentPublic → ModerationPublic` exists for
the provider. The new contract lives in `ModerationPublic`, which Moderation's
own service already depends on.

## 6. Testing strategy

Integration (Pest feature) tests, default level:
- **Moderation**: a topic whose formatter implements the guard — refusal gives
  403, no report row; a topic without it is unchanged.
- **Comment (annotation reports)**: reporting a root and a reply succeeds for
  each permitted role (commenter on an author's reply, author/co-author on a
  reader's annotation and reply, moderator, `user` and `user-confirmed`); refused
  for: unrelated user, own annotation, non-existent id, another commenter's
  annotation as a commenter, trashed root comment. Snapshot content for a root
  and for a reply (with parent). `reported_user_id`, content URL. Report survives
  annotation deletion.
- **DTO**: `can_report` per role and per case (own, emptied, reaction) on both
  `getForComment` and `getForEntity` payloads.
- **Formatter unit**: capture of a missing row, render output.
- **vitest**: none needed beyond what already covers rendering; the buttons are
  template-only.
- **Seeder**: the `annotation` topic gets its default reason.
- **VERIFY (browser)**: button presence/absence per role on both surfaces, the
  modal opening from inside the pop-up and the popover, submission.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where is the A1 server guard enforced? | (A) optional guard contract in Moderation consulted by `createReport`; (B) Comment-owned report route calling a new Moderation API; (C) throw from the formatter's `capture()` | A (user-arbitrated) | Closes the hole at the one endpoint every report goes through, reusable by later topics, no new deptrac edge. B leaves `/report` open to `topic_key=annotation` for any id. C has no reporter argument and surfaces as a 500. |
| 2 | UI hint vs. server rule | duplicate the rule in JS; compute a `can_report` flag in the DTO | DTO flag from the same rule as the guard (decided, not arbitrated) | One source of truth; JS only displays. |
| 3 | Button rendering | PHP `<x-moderation::report-button>`; Alpine `reportButton` inside the existing templates | Alpine inside the templates (decided) | Entity id is a per-row JS value; the component's PHP id is server-rendered. |
| 4 | Seeded reason label | « Autre » literal; the seeder's existing `Other` row | Existing seeder row for the new topic (decided) | The seeder labels every topic « Other »; the admin curates it. Functional spec's « Autre » is that row. |
| 5 | Emptied annotation on the server | refuse in the guard; allow | Allow, hide in UI only (decided) | Spec §4.1.6 lists exactly three refusals; refusing more would be an invented requirement. Harmless either way. |
