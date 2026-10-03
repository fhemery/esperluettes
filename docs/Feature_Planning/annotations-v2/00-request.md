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
