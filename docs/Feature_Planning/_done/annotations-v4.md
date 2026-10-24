# Chapter annotations — v4 (per-annotation report)

**Status:** DONE — 2026-10-24 · **Domain(s):** `Comment`, `Moderation`

## What it does

A single annotation or annotation reply can be reported to moderators with
« Signaler », instead of the whole root comment (reopens v1 decision #3). The
button appears in the « N annotations » pop-up (all widths) and in the v3
in-chapter popover (`md+`, flag icon only). Reports go to a new moderation topic
`annotation` (entity id = `comment_annotations.id`, roots and replies alike)
with a frozen snapshot. To keep private threads private, Moderation gained an
optional per-topic **report guard** that can refuse a report with 403.

## Key behaviour

- Who may report: anyone who can see the thread (commenter on own root,
  authors/co-authors, moderators), `user` and `user-confirmed` alike, never the
  writer of the row. Visibility is not widened.
- Server guard (`canReportById`) refuses, in order: missing / soft-deleted
  annotation, reply whose root is gone, trashed root comment, viewer with no
  role on the comment, commenter on someone else's root, reply by a deactivated
  writer, the writer themself. Anonymised rows (`author_id` null) stay reportable.
- **Emptied annotations are reportable through the API** (A10); only the UI
  hides the button. Hint and guard therefore differ on purpose there.
- `can_report` on `AnnotationDto` is a display hint from the same service; a
  freshly posted reply (`reply()` return) carries `false` (it is the viewer's).
  Pending (client-only) rows have no flag, so no button.
- Snapshot: `highlighted_text` (rendered escaped — stored unsanitized) + `body`
  (rendered raw, purified at write); for a reply also `parent` (read
  `withTrashed`). It survives deletion/emptying of the annotation.
- « Ouvrir » / content URL = root comment URL on the chapter
  (`?comment=<root id>`), `/` if rows are gone. No auto-open of the pop-up.
- `reported_user_id` = annotation `author_id`, `0` if null/gone.
- Duplicate reports by the same user are accepted, as for every topic (A2).
- No notification, no event, no setting added.

## Where the code lives

| Concern | Path |
|---------|------|
| Report guard contract | `app/Domains/Moderation/Public/Contracts/ReportGuardInterface.php` |
| Guard call / 403 mapping | `Moderation/Private/Services/ModerationService.php` (`createReport`, before capture) · `Moderation/Private/Controllers/ModerationReportController.php` (`AuthorizationException` → 403 JSON) |
| Report rule (hint + guard) | `app/Domains/Comment/Private/Services/AnnotationAccessService.php` (`canReport`, `canReportById`, `isWriter`) |
| Topic formatter | `Comment/Private/Support/Moderation/AnnotationSnapshotFormatter.php`, registered in `Comment/Public/Providers/CommentServiceProvider.php` |
| Snapshot view / labels | `Comment/Private/Resources/views/moderation/annotation-snapshot.blade.php` · `lang/fr/moderation.php` (`annotation_*`) |
| DTO flag | `Comment/Public/Api/Contracts/AnnotationDto.php` · `AnnotationPublicApi::buildItems` / `toReplyDto` |
| Pop-up button | `Comment/Private/Resources/views/components/partials/annotation-modal.blade.php` (`data-testid="annotation-report"`) |
| Popover button | `Comment/Private/Resources/views/components/annotation-overlay.blade.php` (`data-testid="annotation-popover-report"`) |
| Dev reason | `Moderation/Database/Seeders/ModerationSeeder.php` (`annotation` → « Other ») |
| PHP tests | `Moderation/Tests/Feature/ReportGuardTest.php` · `Comment/Tests/Feature/Annotations/{ReportAnnotationTest,AnnotationCanReportFlagTest,AnnotationOverlayComponentTest}.php` · `Comment/Tests/Feature/Views/AnnotationModalReportButtonTest.php` · `Comment/Tests/Unit/AnnotationSnapshotFormatterTest.php` |
| E2E | `e2e/tests/core/chapter-annotation-report.spec.ts` |

No migration, no new route, no JS file changed, no new deptrac edge.

## Extension points used

- `ModerationRegistry` — second Comment topic `annotation` with
  `AnnotationSnapshotFormatter`.
- New Moderation extension point `ReportGuardInterface`: optional, implemented
  by a formatter; called with (entity id, authenticated user id) before
  `getReportedUserId` / `capture`. Formatters without it are unchanged.
- Global Alpine `reportButton(topic, id)` from `moderation.js`, used inline in
  the x-for templates (not the PHP report-button component, A8).

## Decisions worth remembering

- Roots **and** replies are reportable; offered on both surfaces (#2, #3).
- Guard lives in Moderation as an optional contract, refusal = 403 (#7); it is
  the reason a private annotation cannot be pulled into a snapshot by id.
- Topic key is `annotation`, not vision-spec's `chapter-annotation` (A5) —
  changeable only while no report exists.
- Moderators see « Signaler » as on comments (A3); content actions stay the
  pop-up's « Supprimer » / « Vider le contenu ».

## Counter-intuitive bits (code vs plan)

- Popover, current annotation: the button is in an `x-for` over
  `current.can_report ? [current.id] : []` keyed on the id, **not** the planned
  `x-if` — paging keeps `current` truthy and `reportButton()` reads its id
  once at init, so `x-if` would report the wrong annotation.
- Buttons are `color="error"` **without** `:outline` (plan said outline;
  VERIFY found the outline variant illegible, fixed in `9ff01e88`).
- The chapter page now always contains the « Signaler » label (annotation
  partial), so `Story/.../ViewChapterTest` asserts on `reportButton('chapter'`
  instead of the label.
- The outside-click risk on the popover (plan phase 5) did not materialise;
  no handler change.

## Not done

- Non-goals (spec §8): image annotation (→ `annotations-images/`), content
  actions on the admin report page, auto-opened pop-up from « Ouvrir »,
  moderator in-chapter overlay, guards on other topics, notifying anyone,
  reasons beyond « Autre ».
- Cut mid-build: nothing.
- Pushed back to the backlog:
  - [`annotation-report-prod-reason/`](../annotation-report-prod-reason/00-request.md) —
    the « Autre » reason is only in the dev seeder; production has none.
  - [`moderation-report-refusals/`](../moderation-report-refusals/00-request.md) —
    403 in the report modal redirects to `/login`; moderator-only accounts see
    the button but the `moderation/report` route gate (`user`/`user-confirmed`)
    refuses them.
  - [`annotation-report-e2e-fixtures/`](../annotation-report-e2e-fixtures/00-request.md) —
    reaction and co-author-reply rows not covered in the browser (PHP covers the rules).
- Unverified: duplicate modal name `report-modal-annotation-<id>` if the same
  annotation's form is opened from both surfaces on one page.
- E2E: `chapter-annotation-report.spec.ts` lives in `core/` (promoted in BUILD,
  reason in its header: Alpine button nested in two x-for templates with an
  injected modal). VERIFY's `e2e/tests/features/annotations-v4.spec.ts`
  (untracked) is proposed for deletion, per `wrap-task` §4.
