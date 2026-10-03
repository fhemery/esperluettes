# Chapter Annotations (v1) — Functional Specification

> **Scope.** v1 only. Everything cut from v1 (in-chapter display, post-publish
> editing, replies, reactions, filter menu, per-annotation report, image
> annotation…) moved to [`annotations-v2/`](../annotations-v2/00-request.md),
> together with the original full vision spec. Revised 2026-10-03; decisions
> in [`DECISIONS.md`](./DECISIONS.md).

## 1. Overview

Today readers leave feedback on a chapter with a single root comment below it.
Annotations let a reader comment **inline while reading**: highlight a passage,
attach a short note to it, accumulate notes as drafts, and publish them
**together** with their root comment. The chapter author then reviews each
commenter's annotations from the comment list and can mark them as processed.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| **Comment** | The existing root comment posted below the chapter (≥ 140 characters). |
| **Annotation** | A short note (≥ 1 non-whitespace character) anchored to a passage of the chapter text. Always attached to a root comment by the same user. |
| **Highlighted text** | The exact plain-text snippet selected when creating the annotation. |
| **Anchor** | Highlighted text + up to 5 words of plain text before (prefix) and after (suffix). Stored as is; not re-located in v1. |
| **Draft** | An annotation saved locally (browser storage), not yet published. |

## 3. Roles & visibility

| Role | Can create annotations | Sees |
|------|----------------------|------|
| **Guest** | No | Nothing |
| **Reader** (not an author of the story) | Yes, on chapters they can read, **until they post their root comment** on that chapter | Their own drafts and published annotations |
| **Chapter author / co-author** | No (cannot comment on own chapter) | All published annotations on the chapter |
| **Moderator** | Yes, as a reader | All published annotations on chapters they can view |
| **Other readers** | — | Nothing of anyone else's annotations |

Drafts are visible to no one but their writer — not even the chapter author.

In v1 annotations only travel with a new root comment: a reader who has already
posted their root comment on a chapter can no longer annotate it.

## 4. Functional requirements

### 4.1 Creating an annotation

- Selecting text in the chapter shows the existing floating toolbar; it now
  offers **« Annoter »** next to « Citer » (each shown only to users allowed to
  use it).
- « Annoter » opens a small form under the selection: a minimal editor
  (**bold**, *italic*, emojis only), **Enregistrer** and **Annuler**.
- Save (button or Ctrl/Cmd+Enter; focus-out does not save) stores the
  annotation **as a draft in local storage**. Cancel discards it.
- Body: 1 to 1000 plain-text characters.

Highlight constraints:

- Only **text blocks** are annotatable — not images, captions or chapter-choice
  blocks. « Annoter » is hidden when the selection covers text outside a text
  block, as « Citer » is.
- A selection may span several paragraphs **within one block**; a selection
  across two blocks is refused with an inline error, as for quotes.
- Highlighted text: at most 500 plain-text characters (the toolbar shows
  « Sélection trop longue » beyond).
- Blank selections show no toolbar. Overlapping annotations by the same user
  are allowed.
- Only the chapter body is annotatable — never `author_note`.

### 4.2 Reviewing drafts

When the reader has at least one draft, a banner above the root-comment form
reads *« {N} annotations, écrivez votre commentaire pour les sauvegarder »*
with a **« Voir les annotations »** button. It opens a pop-up listing the
drafts: highlighted text as a quote, the body, **Modifier** and **Supprimer**.

Drafts and the in-progress root comment survive a page reload; they are keyed
by user and chapter, so another user on the same device never sees them.

### 4.3 Publishing

Submitting the root comment (existing form, ≥ 140 chars) publishes it **and
all drafts atomically**. On success the drafts are cleared; on failure nothing
is published, the drafts stay, and the error is shown. A root comment without
annotations works exactly as today. Credits and notifications are unchanged
(one root comment = one credit, one `ChapterRootCommentNotification`).

### 4.4 Annotations under a root comment

Each root comment with at least one published annotation shows an
**« N annotations »** button between its header and its body — to its writer,
to the chapter authors / co-authors and to moderators; hidden from everyone
else. It opens a pop-up with **that commenter's** annotations only, one row
each: highlighted text as a plain-text quote, then the body.

| Viewer | Per-row actions |
|--------|-----------------|
| Commenter (own) | none — read-only |
| Chapter author / co-author | **Marquer comme traitée / non traitée** (immediate) |
| Moderator | **Supprimer l'annotation** (immediate) |

"Processed" is visible to the chapter authors only — never to the commenter —
reversible, and shared by all co-authors. Processed rows stay listed, with a
marker.

## 5. Anchoring

At creation the client stores `highlighted_text`, `prefix` and `suffix` from a
canonical plain-text view of the chapter — the same one Quote uses: text
blocks only, tags stripped, custom emojis as `:name:`, one newline per block
boundary. The three fields are immutable. v1 never re-locates them (nothing is
shown in the chapter); they are kept for v2's in-chapter display.

## 6. Constraints

| Constraint | Value |
|-----------|-------|
| Annotation body | 1–1000 plain-text chars, non-blank |
| Highlighted text | ≤ 500 plain-text chars |
| Anchor context | ≤ 5 words each side, may be empty at chapter edges |
| Annotations per (user, chapter) | No hard cap |
| Editor formatting | Bold, italic, custom emojis (dedicated Editor preset) |
| Root comment | ≥ 140 chars, unchanged; may be posted without annotations |
| Annotations without a root comment | Never |

## 7. Notifications & events

None. Publishing a root comment with annotations fires exactly what a root
comment fires today. No domain event for annotations.

## 8. Moderation & lifecycle

- No per-annotation Report in v1: reports target the root comment (existing
  flow). Moderators act on single annotations from the pop-up (§4.4).
- Deleting **or emptying** a root comment by moderation deletes its annotations.
- User deleted → their annotations stay, author anonymised (as comments).
  Deactivated → hidden; reactivated → restored.
- Chapter deleted → its comments and annotations go with it.

## 9. Out of scope (v1)

Everything in [`annotations-v2/`](../annotations-v2/00-request.md): quick-emoji
reactions, in-chapter display (tint, margin avatars, popover, mobile/tablet
variants) and re-anchoring, filter menu, post-publish add/edit/delete, replies,
per-annotation report, moderator empty-content on an annotation, image
annotation. Also: cross-device draft sync, annotations on stories, bulk
operations.

## 10. Decisions

The pre-loop decisions that still bind v1 (former §11, full list in
`annotations-v2/vision-spec.md`): body ≤ 1000 (#1); highlight ≤ 500 (#2);
context-based anchor, 5 words each side, client-side only (#4, #5); no
notifications (#8); cascade on root-comment delete (#9); processed flag on
root annotations, author-only, reversible (#10–#12); Save button or
Ctrl/Cmd+Enter only (#24). Revisions of 2026-10-03 are in `DECISIONS.md`
(#1–#4).

## 11. User flow

1. A reader highlights a sentence in a chapter → « Annoter » → types a note →
   Enregistrer. The draft is stored locally.
2. They annotate two more passages, then reach the comment form: the banner
   says « 3 annotations… ». « Voir les annotations » → they delete one and edit
   another.
3. They write their 140-char comment and submit. Comment + 2 annotations are
   published together; the banner is gone. Their comment shows « 2 annotations ».
4. The author opens the chapter, scrolls to comments, clicks « 2 annotations »
   on that comment, reads them and marks one as processed. The reader never
   sees that flag.
5. A moderator, after a report on the root comment, opens the same pop-up and
   deletes an offending annotation.
