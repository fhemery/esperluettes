# Chapter annotations — v3 (in-chapter display) — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.
>
> Background: [`vision-spec.md`](./vision-spec.md) (the original full spec) and
> the v1/v2 records [`_done/annotations.md`](../_done/annotations.md),
> [`_done/annotations-v2.md`](../_done/annotations-v2.md). Where this document
> and the vision spec disagree, **this document wins**.

## 1. Overview

v1 and v2 keep annotations inside the « N annotations » pop-up below the
chapter. v3 brings them **into the chapter text**: annotated passages are
tinted, the annotator's avatar sits in the right gutter next to the passage,
and a popover lets the commenter edit their own annotations and lets the author
read, reply to and triage everyone's. Authors get a filter menu; moderators
get « Vider le contenu » on a single annotation; the pop-up flags annotations
whose passage has disappeared.

## 2. Vocabulary

Vision-spec §2 still applies (annotation, reaction, reply, anchor, draft,
pending change, missing annotation). New or sharpened here:

| Term | Meaning |
|------|---------|
| **Teinte d'annotation** | The visual marking of an annotated passage in the chapter text. Distinct in style from Quote's yellow reader tint (assumed: a coloured underline rather than a background). |
| **Gouttière** | The narrow column just right of the chapter article, from `md` up, already used by Quote's author heat map pills. Annotation avatars live in it. |
| **Groupe** | Annotations whose gutter positions fall within ~20px vertically; shown as one avatar with a « +N » badge. |
| **Popover d'annotation** | The panel opened by clicking a gutter avatar. |
| **Menu de filtre** | The author's filter menu: commenter checklist + « Afficher les annotations traitées ». |
| **Ancre `ok` / `moved` / `missing`** | Result of re-locating an annotation in the current chapter text (shared `findAnchor`): exact triple found / only prefix…suffix found / not found. |
| **Vider le contenu** | Moderator action replacing one annotation's body with « <Annotation supprimée> ». |

## 3. Roles & visibility

| Role | Can see (in-chapter, `md`+) | Can do |
|------|---------|--------|
| Guest | Nothing | Nothing |
| `user` / `user-confirmed`, not author, with annotations on the chapter (the **commenter**) | Their own drafts, pending adds and published annotations: tint + gutter avatars + popover. Never the processed flag. Never anyone else's annotations. | From their own popover: edit / delete (as drafts before the root comment, as pending changes after), reply when v2's reply rule allows. |
| Any other reader (incl. beta readers who haven't annotated) | Nothing | — |
| Author / co-author of the chapter | Every published annotation (whose root comment is visible): tint + gutter avatars + popover + filter menu | From the popover: « Répondre », « Marquer comme traitée / non traitée » (immediate). Filter the gutter. |
| Moderator (moderator, admin, tech-admin) not author | **No in-chapter display** (#4). Pop-up as today. | In the pop-up: « Supprimer » (as today) and new « Vider le contenu » on a root annotation. |

The `user` / `user-confirmed` distinction is unchanged from v2: whoever
`ChapterCommentPolicy::canAnnotate` lets annotate sees their own annotations
in-chapter. v3 adds no new role rule.

Below `md`, every viewer who sees annotations gets the **tint only** (§4.6).

## 4. Functional requirements

### 4.1 Re-anchoring on every chapter view

1. When a viewer entitled to an in-chapter display (commenter or author) opens
   the chapter, each annotation they may see is re-located in the chapter's
   canonical text (vision §5.2, already implemented as the shared
   `findAnchor`).
2. `ok` → tint on the matched passage. `moved` → tint on the new passage,
   with no extra marker (assumed). `missing` → no tint, no avatar.
3. Annotations only ever anchor in text blocks (v1 rule); image and
   chapter-choice blocks get no tint.

### 4.2 Tint and gutter avatars (`md`+)

1. Every `ok`/`moved` visible annotation tints its passage with the
   annotation tint.
2. For the commenter, their own quote tint (yellow, clickable) keeps showing.
   Where a quote and an annotation overlap, both are visible (distinct
   styles); clicking the quote tint still opens the quote (#3).
3. An avatar of the annotator is placed in the gutter, level with the passage.
   The tint itself is not clickable for annotations; the avatar is the entry
   point.
4. Annotations whose avatar positions are within ~20px are grouped: one avatar
   (the first **visible** annotation in reading order) and a « +N » badge, N =
   the number of *other* root annotations in the group (assumed reading of
   vision #6; replies never count).
5. Groups recompute live when visibility changes (filter, processed toggle,
   pending delete, new draft) — the lead avatar switches to the next visible
   annotation; an empty group disappears.

### 4.3 Commenter's view and popover

1. The commenter sees: drafts (before the root comment), published annotations
   with their pending edits applied, and pending adds. Annotations with a
   pending delete are hidden until the change is saved or undone.
2. New drafts / pending adds appear in the text as soon as they are created
   (toolbar « Annoter » or reaction), without a reload.
3. Clicking an avatar opens the popover (one annotation at a time, « 1 / N »
   with arrows when grouped): body, reply thread, « Modifier », « Supprimer »,
   and « Répondre » when v2's reply rule allows (the commenter may answer once
   an author has replied on that annotation).
4. « Modifier » / « Supprimer » behave exactly as in the pop-up and the
   Annotations tab: they update the draft before the root comment exists, and
   queue a pending change (Save banner) after. Replies are immediate (v2).
5. An annotation emptied by a moderator (§4.7) shows its placeholder body and
   offers « Supprimer » only, not « Modifier ».
6. Error paths: a failed reply keeps the typed text and shows the error, as in
   the pop-up. Pending changes that go stale behave as in v2 (#14 there).

### 4.4 Author's view and popover

1. The author sees every published annotation of every commenter whose root
   comment is visible, processed ones hidden by default.
2. Clicking an avatar opens the popover on the group: one annotation at a time,
   « 1 / N » and `<` / `>` arrows. Each shows the commenter's name and avatar,
   the body, the reply thread, « Répondre », and « Marquer comme traitée » /
   « Marquer comme non traitée ».
3. Both actions are immediate. After a reply, the author sees the v2 hint
   about replying on the root comment to notify the commenter.
4. Marking processed while « Afficher les annotations traitées » is off hides
   the annotation from the gutter and the text at once; the group recomputes;
   if it was the last visible one, the popover closes.
5. When processed annotations are shown, they carry a visual « traitée » hint
   (assumed: dimmed avatar/tint).
6. No moderation or report action in the popover, even for an author who is
   also a moderator.

### 4.5 Author's filter menu

1. Shown to authors / co-authors only, at the top of the article (`md`+),
   when the chapter has at least one visible annotation (assumed).
2. Contents: a checklist of every commenter who annotated the chapter (name +
   avatar), all ticked by default; and « Afficher les annotations traitées »,
   off by default.
3. Unticking a commenter hides their annotations (tint and avatars) in the
   chapter. The pop-up is never filtered.
4. State lasts for the visit only: a reload resets it.
5. Not shown to the commenter (they only see their own annotations).

### 4.6 Coexistence with Quote's author heat map

1. The heat map stays off on page load and is switched on by the author, as
   today.
2. **Heat on → annotation tint, avatars and filter menu are hidden. Heat off
   → they come back** in the state they were in (#2). Only one overlay owns the
   gutter and the text at a time.

### 4.7 Below `md`

1. The annotation tint is shown (commenter: own; author: all visible), with no
   avatar, no popover and no filter menu. Tapping the tint does nothing.
2. Review stays in the pop-up and the Annotations tab, as today. Creating
   annotations works as today.

### 4.8 Moderator « Vider le contenu » on one annotation

1. In the « N annotations » pop-up, a moderator sees « Vider le contenu » next
   to « Supprimer » on each **root** annotation (replies keep « Supprimer »
   only — assumed).
2. Confirming replaces the body with « <Annotation supprimée> ». Anchor, tint,
   replies and the processed flag are kept (#5; processed kept is assumed).
3. Immediate; the row updates in place. Everyone who can see the annotation
   sees the placeholder on their next render.
4. The commenter can no longer edit an emptied annotation; they can still
   delete it. A pending edit on it made before the emptying goes stale on save
   (v2 stale path).
5. Emptying an already-emptied annotation is harmless.

### 4.9 « Passage absent du chapitre » in the pop-up

1. When the « N annotations » pop-up opens, each row is re-anchored against the
   current chapter text.
2. A `missing` row keeps its quoted original snippet and shows the badge
   « Passage absent du chapitre ». `moved` rows show no badge.
3. Applies to every viewer of the pop-up (commenter, author, moderator).

### 4.10 Purifier emoji list (no visible change)

The custom-emoji class list shared by the five purifier profiles is defined
once, so a new emoji is added in one place. No user-visible change.

## 5. Lifecycle

- **Chapter edited** → re-anchoring on next view: `ok` / `moved` / `missing`
  (§4.1); `missing` annotations leave the text and get the pop-up badge.
- **Root comment deleted or emptied** → its annotations are already
  soft-deleted (v1) and vanish from the chapter.
- **Commenter deactivated** → their comments are soft-deleted (existing
  listener), so their annotations disappear from the chapter; reactivation
  follows the existing comment behaviour.
- **Commenter deleted** → comment and annotations kept, anonymised; in-chapter
  they show the default avatar and the anonymised name (assumed), and appear in
  the filter checklist under that name.
- **Reply writer deactivated** → filtered at read time, as v2.
- **Moderator empties an annotation** → §4.8.
- **Chapter unpublished / story deleted** → nothing new: whoever can no longer
  open the chapter sees nothing.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | §3. Commenter (own), author/co-author (all), moderators pop-up only (#4). |
| Visibility / privacy | Unchanged from v2: other readers and guests see nothing; processed flag never reaches the commenter — the in-chapter payload must not leak it (server-side test). |
| Settings | N/A — filter is per-visit; persistent toggle stays out of scope. |
| Notifications | None (v1/v2 rule). Emptying an annotation notifies nobody (assumed). |
| Domain events | None, including for « Vider le contenu » (assumed, consistent with moderator delete). Existing listeners (user deactivated, comment moderated) unchanged. |
| Statistics | N/A |
| Moderation | « Vider le contenu » on root annotations from the pop-up (#5). No per-annotation report (v4). No moderation action in the in-chapter popover. |
| Lifecycle / cascade | §5. |
| Media | N/A — avatars come from existing profiles. |
| Search | N/A |
| i18n | French: « Passage absent du chapitre », « <Annotation supprimée> », « Vider le contenu », « Marquer comme traitée / non traitée », « Afficher les annotations traitées », « +N », « 1 / N », filter menu labels. Placeholder pluralisation N/A. |
| Mobile | Below `md`: tint only (§4.7). |
| Accessibility | Avatars and arrows are keyboard-focusable buttons with a label naming the commenter and the count; the popover is reachable and closable by keyboard (assumed — same bar as Quote's pills). |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Where do avatars go? | Quote's 24px gutter, `md`+, same layout at `md` and `lg`; no inline icon; filter menu at the top of the article |
| 2 | Author's heat map vs annotations | Heat on replaces the annotation overlay (tint, avatars, filter menu); heat off restores it |
| 3 | Reader's quote tint vs annotation tint | Both shown, distinct styles; quote tint stays clickable, avatar opens the annotation |
| 4 | Moderators' in-chapter display | None — pop-up only |
| 5 | Moderator « Vider le contenu » | Body → « <Annotation supprimée> »; anchor, tint and replies kept; commenter can delete but no longer edit |
| 6 | Commenter's own popover | Full: « Modifier » / « Supprimer » (drafts or pending changes), « Répondre » per v2 rule; pending-deleted annotations hidden from the text |
| 7 | Replay of the whole flow and its assumptions | Approved as written |

## 8. Out of scope

- Per-annotation Report and image annotation (v4).
- In-chapter avatars / popover below `md`; tapping the tint on mobile.
- In-chapter display for moderators.
- Showing the annotation overlay and the heat map at the same time.
- A persistent (Settings-backed) annotation or filter toggle.
- Re-anchoring an annotation to a new passage by hand, server-side
  re-anchoring, fuzzy matching, resolving duplicate-triple collisions.
- Surfacing the processed flag to the commenter.
- « Vider le contenu » on replies; restoring an emptied annotation.
- Notifications, events, statistics for anything in v3.
- Moving the in-chapter UI into the empty `lg` 230px right column.

## 9. Open questions

| # | Question | Blocking? |
|---|----------|-----------|
| 1 | Exact annotation tint style (underline colour, dark mode) and the processed « dimmed » hint — UI detail for DESIGN/BUILD within decision #3. | non-blocking |
| 2 | Renderer coordination with Quote (shared `<mark>` wrapping vs CSS Custom Highlight API) — technical, for DESIGN (see `00-request.md`). | non-blocking |
