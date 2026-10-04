# Chapter annotations — v2 (writer side)

**Status:** DONE — 2026-10-04 · **Domain(s):** `Comment` (core), `Story` (policy + chapter page) · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md) · v1: [`_done/annotations.md`](../_done/annotations.md)

## What it does

Completes the writer side of v1 annotations, still entirely inside the « N annotations » pop-up (no in-chapter
display yet). Readers react with ❤️ 🔥 👍 in one click from the selection toolbar; once their root comment is
posted they keep annotating, and edit / delete their annotations, as **pending changes** in localStorage, saved
from a sticky banner in **one atomic `PUT`**. Authors/co-authors reply under any annotation; the commenter may
answer once an author has. No migration: v1's `parent_annotation_id`, `is_processed` and soft deletes carry it all.

## Key behaviour

- **Who annotates:** `ChapterCommentPolicy::canAnnotate` = logged-in and not author/co-author, **root comment or not**
  (v1's A5 lifted; an emptied root still qualifies, #6). Posting annotations *with a new root* is still refused
  by `canCreateRoot`, now checked inside `CommentPublicApi::validateAnnotations` so the error stays on `annotations`.
- **Mode:** `ChapterController::show` sets `data-annotation-mode` = `draft` (no root yet → v1 drafts slot, posted
  with the root) or `pending` (root exists → `annotationChanges.adds`) plus `data-root-comment-id`.
- **Save:** `saveChanges` = root's writer + `canAnnotate`, else 403; trashed root 404. All item errors collected
  into one 422 keyed `adds.<clientKey>` / `edits.<id>` / `deletes.<id>`; **any** bad id (deleted, foreign, reply,
  other comment) is `errors.stale`, never 403. Transaction order: deletes (with replies) → edits → adds.
- **Edit** changes the body only (anchor immutable) and resets `is_processed` (#4). **Delete** takes replies (#7).
- **Reply rule** (`AnnotationAccessService::canReply`): `author` role on any root; `commenter` on own root only if a
  *visible* reply by someone ≠ root writer exists (derived, no column, #12); moderators never. Replies: no
  edit, writer deletes own immediately (#9), moderator « Supprimer » works on them, one level only (422 otherwise).
- **Deactivated reply writer:** filtered at read time via `AuthPublicApi::getUsersById`; no row touched (#10).
  Deleted user: kept, anonymised (`author_id` null). An anonymised reply does **not** unlock the commenter.
- **Visibility:** replies seen by whoever sees the root (commenter, authors, moderators). Processed flag still
  never sent to the commenter. Counts are all zero on a type without `supportsAnnotations()`.
- **No** notification, event, statistic or moderation topic (#3, A4). Author gets a one-line hint after replying.
- **Toolbar:** « Annoter » and reactions carry `data-requires-single-area` (new opt-in in Comment's
  `toolbar.js`): a two-text-block selection hides them, Quote's « Citer » keeps its looser rule (#13).
- **Stale pending changes survive reloads:** the pop-up lists pending edits/deletes absent from the server list
  as their own rows, pre-flagged `errors.stale` with « Retirer » (#14) — never auto-pruned (would lose text).

## Where the code lives

| Concern | Path (under `app/Domains/Comment/` unless noted) |
|---------|------|
| Public API | `Public/Api/AnnotationPublicApi.php` (`saveChanges`, `reply`, `deleteOwnReply`; `getForComment` now fills `replies`, `can_edit`, `can_reply`) |
| DTOs | `Public/Api/Contracts/AnnotationChangeSetDto.php`, `AnnotationToCreateDto` (`clientKey`), `AnnotationDto` (`canEdit`, `canReply`) |
| Services | `Private/Services/AnnotationService.php`, `AnnotationAccessService.php`, `Private/Support/AnnotationItemValidator.php` (shared by create-with-root, saves, replies) |
| Routes / controllers | `PUT /comments/{id}/annotations` → `AnnotationController@save`; `POST /comments/annotations/{id}/replies`, `DELETE /comments/annotations/replies/{id}` → `AnnotationReplyController` |
| Requests | `Private/Requests/SaveAnnotationChangesRequest.php` (shape only), `StoreAnnotationReplyRequest.php`; `StoreCommentRequest` lost its hard-coded `max:500` |
| Views | `components/reaction-buttons.blade.php`, `partials/annotation-changes-banner.blade.php`, `partials/annotation-modal.blade.php`, `partials/comment-item.blade.php` (hidden 0-count button on own root) |
| JS | `Resources/js/annotations/{reactions,changes-banner,replies,modal,capture-form,api}.js`, `comment-draft/index.js` (schema v2, `annotationChanges` slot), `annotable/toolbar.js` |
| Story | `app/Domains/Story/Private/Services/ChapterCommentPolicy.php`, `Private/Controllers/ChapterController.php`, `chapters/show.blade.php` |
| Tests | `Tests/Feature/Annotations/{SaveAnnotationChangesTest,AnnotationRepliesTest,AnnotationCountTest}.php`, `Tests/Unit/AnnotationItemValidatorTest.php`, `Tests/helpers.php`, vitest beside each JS file; Story `ChapterCommentPolicyIntegrationTest`, `ChapterAnnotateButtonViewTest`; e2e `e2e/tests/features/annotations-v2.spec.ts` (to retire, see below) |

## Extension points used

- `CommentPolicy` contract (Story's `ChapterCommentPolicy::canAnnotate` meaning changed; News stays `false`).
- Comment's selection toolbar `toolbar-actions` slot (`<x-comment::reaction-buttons>` beside « Annoter »), plus
  the new `data-requires-single-area` attribute, available to any contributing domain.
- Comment-draft store: fourth slot `annotationChanges` and event `comment-drafts:annotation-changes-changed`.

## Where the code differs from the plan / architecture

- Arch §1.1/§4 and plan phase 8 had a cross-block emoji click be a silent no-op; the spec said *hidden*. VERIFY
  caught it; fixed by #13 (opt-in single-area rule). The no-op stays as defence in depth in `reactions.js`.
- Stale-after-reload (A16) was not designed; fixed by #14 in `modal.js` `rows`.
- Not in the architecture: the banner's « Voir » button, the hidden 0-count « N annotations » button on the viewer's
  own root (also in lazy-loaded fragments via `CommentController@items`), banner labels `item_edit`/`item_delete` (A12).
- Plan open item 2: foreign/reply ids answer 422 `stale`, not 403 as arch §6 hinted.

## Decisions worth remembering

- One `PUT` with an operation list, all-or-nothing; errors keyed per item so the client can name it (#2, A3).
- Pending changes live in the comment-draft key, not a separate store; the root consumed marker leaves them (#11).
- "An author has replied" is derived from writers, not stored (#12) — revisit if moderators/beta readers ever reply.
- `canAnnotate` changed meaning instead of adding a policy method (arch §7 #4).

## Assumptions made without asking — reversible

| # | Assumption |
|---|------------|
| A1 | Replies show writer name + date; visible to commenter, authors, moderators only |
| A2 | Commenter's pop-up overlays pending markers on saved rows, lists pending adds, per-row undo |
| A3 | One stale item fails the whole save; the error names it; the user discards it |
| A4 | No events, statistics or moderation topic |
| A5 | v1 leftovers folded in (policy highlight cap, gated counts, beta-reader test, e2e helpers); emoji-list de-dup → v3 |
| A6 | v3 = D+E+G (+ emoji de-dup); v4 = F+H |
| A7 | `PUT` JSON body → `TrimStrings` trims edge spaces of `prefix`/`suffix` on pending adds (v1 path keeps them); left as is |
| A8 | `annotatedChapterCommentPayload` / `annotationItem` moved to `Comment/Tests/helpers.php` (file-local Pest helpers broke in parallel) |
| A9 | Deactivated-writer reply tests use a non-collaborator standing in for a *former* co-author (a current one's deactivation deletes the story) |
| A10 | `postReply` / `deleteReply` throw on any non-2xx; `setPendingEdit` does not refuse an id pending deletion |
| A11 | `<x-comment::reaction-buttons>` takes `entity-type` (cap + user id on its root); entity id and mode read from the annotable holding the selection |
| A12 | Banner names a refused add by its passage, an edit/delete generically; 404 marks every item stale |
| A13 | Moderator delete stays immediate; pending deletes only for `commenter`; pending adds have only undo in the pop-up |
| A14 | One reply editor moved under the answered row; empty/too-long refused client-side; « Répondre » follows `can_reply` only |
| A15 | E2E on `chapitre-illustre-7`; `LONG_PARAGRAPH` kept; ten callerless v1 helpers deleted |
| A16 | *Superseded by #14* (stale edit after reload could only be cleared by « Tout annuler ») |
| A17 | *Superseded by #13* (cross-block selection kept « Annoter » and emoji visible) |
| A18 | « Supprimer la réponse » switched from outline to filled `danger` (contrast 1.2:1); shared outline style untouched |
| A19 | Beta reader, deactivated/deleted writer, news rows verified by PHP tests only; `citeButton` locator → `.quote-toolbar-btn` |

## Not done

- **Non-goals (spec §8):** editing replies, deeper threads, moderator replies, notifications/events, cross-device
  sync of pending changes, re-anchoring an annotation (delete + re-create), reactions beyond ❤️ 🔥 👍.
- **Backlog:** in-chapter display, filter menu, moderator « Vider le contenu » on one annotation, purifier
  emoji-list de-dup → [`annotations-v3/`](../annotations-v3/00-request.md) (now holds `vision-spec.md`);
  per-annotation Report, image annotation → [`annotations-v4/`](../annotations-v4/00-request.md).
- **Known limits:** pending changes are browser-local (a cleared browser loses them silently); the deactivation
  filter costs one Auth call per pop-up open; chapter edits can orphan anchors until v3.
- **E2E spec — pending the user's call:** `e2e/tests/features/annotations-v2.spec.ts` is still in place. Default is
  delete, but ~35 page-object helpers (`ChapterAnnotations`, `CommentThread.scrollDown/emptyContent/replySubmit/idOf`,
  `ChapterPage.touchSelectText`, …) would go callerless and must be pruned with it. Alternative:
  promote only the round-trip test to `e2e/tests/core/` (pending slot + banner + `PUT` are breakable from the
  comment-draft store and toolbar, the same reason v1's spec is core).
