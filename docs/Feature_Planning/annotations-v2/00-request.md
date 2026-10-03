# Chapter annotations — v2 — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Everything that was cut from v1 of chapter annotations (`annotations/`), to be
handled right after v1 ships. Split out so the v1 documents only carry v1.

The cut list, from the v1 architecture's roadmap (2026-10-03):

| Letter | Theme | Notes |
|--------|-------|-------|
| A | Quick-emoji reactions | ❤️ 🔥 👍 buttons in the selection toolbar; no schema change. |
| B | Post-publish add / edit / delete | Pending changes in local storage, « Vous avez {N} annotations non sauvegardées » save banner, atomic `PUT` batch. Lifts v1's "no annotating once you have commented". |
| C | Replies | One level deep, author ↔ commenter, no notification (+ nudge to reply on the root comment). Schema already supports them. |
| D | In-chapter display | Client-side re-anchoring (`findAnchor`), passage tint, right-margin avatars with "+N" grouping on `md+`, inline icon on tablet, popover with Reply / processed toggle. `missing` badge in the pop-up. |
| E | Filter menu | Commenter checklist + « Show processed », right margin only, per session. |
| F | Per-annotation Report | `chapter-annotation` moderation topic, snapshot formatter, seeded reasons. |
| G | Moderator « Vider le contenu » | On a single annotation. |
| H | Image annotation | Annotate an image block; needs a stable block anchor that does not exist today. |

Small v1 leftovers, found at v1 WRAP (2026-10-03) — fold into whichever item
touches the code, or do them first:

- **A5 lifts with B.** v1 hides « Annoter » once the reader has a root comment
  (`ChapterCommentPolicy::canAnnotate` = `canCreateRoot`). B must change that
  gate, and the drafts banner that lives in the root-comment form.
- **F needs decision #3 reopened**: v1 has no `chapter-annotation` moderation
  topic; reports target the root comment.
- `AnnotationAccessService::visibleCounts` gives authors/moderators a count
  without asking `canAnnotate` (harmless while creation checks it).
- `GET /comments/{id}/annotations` sends `is_processed` to moderators; the
  pop-up shows it to authors only. Decide, then align payload and UI.
- `StoreCommentRequest` hard-codes `highlighted_text` `max:500`, ignoring
  `getAnnotationHighlightMaxLength` — a policy raising it would still be capped.
- The `annotation` purifier profile duplicates the custom-emoji class list of
  the other profiles in `config/purifier.php`; a new emoji must be added twice.
- The annotations bundle loads on every authenticated comment list, news
  included (inert there). Gating it needs `canAnnotate` on `CommentUiConfigDto`.
- No direct PHP test for "a beta reader sees 0" in `AnnotationCountTest`; the
  only seeded beta reader is also a moderator, so the browser cannot isolate it.
- Unused e2e helpers left by the deleted VERIFY spec (`ChapterAnnotations`
  `evidence`/`storedDrafts`/`storedRootBody`/`editDraft`/`allCountButtons`,
  `ChapterPage.touchSelectText`/`quoteMiniForm`/`quotePanel`,
  `LoginPage.logout`): reuse them in v2's specs or prune.

## Why

The functional vision (in-context feedback the author can triage along the
text) is only partly delivered by v1, which stops at "write annotations, post
them with the comment, review them in a pop-up".

## Constraints or ideas I already have

- `vision-spec.md` in this folder is the original full functional spec (copy of
  `annotations/01-functional.md` as of 2026-10-03, with the v1 revisions
  noted). REFINE starts from it rather than from scratch; v1's finished record
  (`_done/annotations.md` once wrapped) says what actually shipped.
- Decisions already settled in `annotations/` apply unless deliberately
  reopened: cross-block selections refused, text blocks only (#1, #2),
  emptying a root comment deletes its annotations (#4). #3 deferred the
  per-annotation Report to here (item F).
- In-chapter display must coexist with Quote's reader tint and author heat.
  Each of those strips its own `<mark>`s, calls `normalize()` and re-wraps; an
  annotation tint would be a third renderer on the same article, invalidating
  the others' node maps if they interleave. Coordinate the renderers (or move
  to the CSS Custom Highlight API), and share Quote's right-margin gutter
  rather than adding a second one.
- v1 left these hooks: `comment_annotations` already has
  `parent_annotation_id` (replies) and `is_processed`; anchors are immutable
  and captured from the same canonical text as Quote; drafts live in the
  comment-draft store's `annotations` slot.
- Items may be cut or reordered at REFINE; this is a list, not a commitment to
  all eight.

## Explicitly out of scope

Anything already in v1.
