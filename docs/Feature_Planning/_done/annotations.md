# Chapter annotations (v1)

> WRAP output — the compact record of the finished feature. Written from the
> code on `feat/annotations`; the planning folder (`01`–`03`, `DECISIONS.md`,
> VERIFY shots) was deleted at archive — git history keeps it.

**Status:** DONE — 2026-10-03 · **Domain(s):** `Comment` (+ `Story`, `Editor`,
`News`, `config/purifier.php`)

## What it does

A reader selects a passage inside one text block of a chapter, clicks « Annoter »
(next to Quote's « Citer » in the shared selection toolbar) and writes a short
rich remark. Annotations are **local drafts** (comment-draft store, `annotations`
slot) until the reader posts their **root comment**: both are written in one
transaction. Afterwards a « N annotations » button on the comment opens a pop-up
listing them; chapter authors mark them processed, moderators delete one. No
in-chapter display, no reactions, no replies, no editing after publish (all v2).

## Key behaviour

- **Annotatable types:** `CommentPolicy::supportsAnnotations()` (type level) —
  Chapter true, News/Default false. When false, `comment-list` renders no
  banner, no pop-up and pushes no annotations script (news pages load none).
- **Who may annotate:** `ChapterCommentPolicy::canAnnotate` = `canCreateRoot`
  (not an author/co-author, no root comment yet on the chapter), never a guest.
- **Who sees what** (`AnnotationAccessService`, precedence commenter → author →
  moderator): commenter = own rows, `is_processed` sent as `null`; author
  (`canMarkAsProcessed` = author/co-author, not beta reader) = all rows, may
  toggle; moderator/admin/tech-admin = all rows **and** `is_processed` in the
  JSON (the pop-up shows the marker to authors only), may delete one. Anyone
  else: 403 on the list, count 0. An author who commented their own chapter is
  a *commenter* there and cannot toggle.
- **Create path is `POST /comments` only** (hidden `annotations` input, JSON).
  Annotation checks run *before* root checks; any refusal rejects the whole post
  under the `annotations` key, nothing written. Refused on a reply.
- **Limits:** body 1–1000 plain chars (`annotation` purifier profile: `p br
  strong em` + custom-emoji `span`), highlight ≤ 500, prefix/suffix ≤ 255.
- **No events.** `CommentPosted` fires once after the transaction; credits,
  notifications, statistics ignore annotations.
- **Lifecycle:** moderator empties root → annotations soft-deleted; moderator
  deletes root or chapter deleted → FK cascade (comments are force-deleted);
  moderator deletes one → row + replies soft-deleted; user deleted →
  `author_id` null, trashed rows included; deactivate/reactivate → **no
  annotation row touched** (root comment's soft delete hides/restores them, so
  moderator removals never come back).
- **Counter-intuitive:** `highlighted_text`/`prefix`/`suffix` are stored
  **unsanitized** — always `x-text`/`{{ }}`. Selections across two blocks are
  refused at capture (form opens with an error, Save disabled); captions, images
  and chapter-choice blocks are excluded (`.ce-block--text` only).

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `Comment/Public/Api/AnnotationPublicApi.php` (`getForComment`, `setProcessed`, `moderatorDelete`); create via `CommentPublicApi::create` + `Contracts/AnnotationToCreateDto` |
| DTOs | `Contracts/AnnotationDto`, `AnnotationListDto` (roles), `CommentDto::annotationCount` |
| Policy contract | `Contracts/CommentPolicy` (+5 methods), `DefaultCommentPolicy`, `CommentPolicyRegistry` |
| Services | `Private/Services/AnnotationService.php`, `AnnotationAccessService.php` (`visibleCounts`: one grouped query per page); `CommentService::postComment` (transaction), `emptyContentByModeration` |
| Controllers / routes | `AnnotationController` (`GET /comments/{id}/annotations`, `PUT /comments/annotations/{id}/processed`), `AnnotationModerationController` (`DELETE /comments/annotations/{id}`, role middleware); `CommentController::store` now `->withInput()` |
| Validation | `Private/Requests/StoreCommentRequest.php` (decodes JSON), `SetAnnotationProcessedRequest.php` |
| Model / migration | `Private/Models/CommentAnnotation.php`; `Database/Migrations/2026_10_03_000001_create_comment_annotations_table.php` |
| Views | `components/annotate-button`, `annotation-form`, `partials/annotation-banner`, `annotation-drafts-modal`, `annotation-modal`; `comment-list` (gated on `annotationsEnabled` from `CommentListComponent`), `comment-item` |
| JS | `Comment/Resources/js/annotations/` (`capture-form`, `drafts`, `modal`, `api`, Vite entry `index.js`); `comment-draft/index.js` (slot API) |
| Story wiring | `ChapterController::show` (`canAnnotate`), `chapters/show.blade.php`, `ChapterCommentPolicy` |
| Shared infra | Editor preset `inline` (`ToolbarPresets.php`); purifier profile `annotation` (`config/purifier.php`); `vite.config.js` entry |
| Tests | `Comment/Tests/Feature/Annotations/*` (8 files), `RenderCommentListComponentTest`, `CommentFragmentControllerTest`, `CommentBodySanitizerTest`; Story `ChapterAnnotateButtonViewTest`, `ChapterCommentPolicyIntegrationTest`; vitest beside each JS file; e2e `e2e/tests/core/chapter-annotations.spec.ts` |

## Extension points used

- `CommentPolicyRegistry` — 5 new policy methods; Story and News implement them.
- `<x-comment::annotable>` `toolbar-actions` slot + `data-requires-selection-within`
  (from `quotable-blocks-opt-in`).
- Comment-draft store — reserved `annotations` slot, `window.commentDrafts.*Annotation(s)`,
  event `comment-drafts:annotations-changed`; a `root` consumed marker clears it.
- Shared anchoring (`extractAnchor`, `block-elements.js`) — capture only, no re-anchoring.
- Editor toolbar presets (`inline`); no moderation topic, no notification, no statistic.

## Decisions worth remembering

- #1/#2: cross-block selections refused; text blocks only (same as Quote).
- #3: no per-annotation Report — reports target the root comment.
- #4: emptying a root comment soft-deletes its annotations.
- #5: deactivate/reactivate touch no annotation row (see lifecycle).
- #6 (WRAP): moderators keep receiving `is_processed` — deliberate, not a gap.
- #7 (WRAP, reverses A11 for news): `supportsAnnotations()` gates the script,
  banner and pop-up per entity type.
- #8 (WRAP): A3/A9 and A7 confirmed as built.
- A5: a reader with a root comment cannot annotate — no post-publish add in v1.

### Assumptions still standing (made without asking, confirmed or untouched)

- A3/A9: user deletion **keeps** annotations anonymised (unlike Quote's hard delete), trashed rows nullified too — confirmed (#8).
- A4: annotations are not fetched on chapter load; the pop-up fetches per comment on first open, caches and mutates in place (A12).
- A7: `CommentController::store` flashes old input on **every** API refusal — confirmed (#8).
- A8: an author who is also the commenter cannot toggle processed.
- A10: capture form also refuses a body over the max (`body_too_long`); limits read from the registry.
- A12: delete in the pop-up is immediate, no confirm.
- A13: the e2e spec lives in `tests/core/` (header gives the reason).

## Where the plan and the code disagree

- Arch §2.3 soft-deletes annotations on root-comment moderator delete and
  cascades deactivate/reactivate: neither built — force-delete + FK cascade, and #5.
- Arch §8 puts the capture form in `partials/`; it is the component
  `<x-comment::annotation-form>`, rendered by Story beside `<x-quote::mini-form>`.
- Arch §1.3 lists Story + Editor as touched; News changed too (interface methods).
- Spec §4.1 "may span blocks" → refused (#1). Spec §11 #20 Report → deferred (#3).
- Plan open item 5 ("bundle on news, not planned") → fixed at WRAP (#7) with a
  policy method rather than a `CommentUiConfigDto` field.

## Not done

- **Non-goals (v1)**, all in [`annotations-v2`](./annotations-v2.md):
  reactions, post-publish add/edit/delete (lifts A5), replies (schema has
  `parent_annotation_id`), in-chapter display/re-anchoring, filter, per-annotation
  Report (#3), moderator empty on one annotation, image annotation. Also out:
  cross-device draft sync, annotations on stories, bulk operations.
- **Known gaps** (in `annotations-v2/00-request.md` as small follow-ups):
  `visibleCounts` ignores `canAnnotate` for authors/moderators;
  `StoreCommentRequest` hard-codes `max:500` on the highlight whatever the
  policy says; the custom-emoji class list is duplicated in the `annotation`
  purifier profile; no direct "beta reader sees 0" PHP test.
- **Pre-existing, seen during VERIFY:** « Comment too short » untranslated;
  restored comment shows « Modifié le » after reactivation (restore touches
  `updated_at`).
- **E2E:** `e2e/tests/features/annotations.spec.ts` deleted at WRAP (default);
  `e2e/tests/core/chapter-annotations.spec.ts` stays. Page-object helpers used
  only by the deleted spec remain (`ChapterAnnotations` `evidence`/`storedDrafts`/…,
  `ChapterPage.touchSelectText`, `LoginPage.logout`) and the `LONG_PARAGRAPH`
  seed fixture.
