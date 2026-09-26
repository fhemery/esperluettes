# Multi-edit — chapter switch block — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

A new block for the chapter Avancé (multi-edit) editor: a group of buttons,
each leading to another chapter of the same story. It lets authors write
interactive "histoire dont vous êtes le héros" stories with proper choice
buttons placed anywhere in the chapter, instead of plain inline links. The
block exists only for story chapters — news and static pages never offer it.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Bloc « Choix de chapitres » | The new multi-edit block: an ordered group of one or more choices. |
| Choix | One button in the block: a target chapter plus an optional label. |
| Chapitre cible | The chapter a choice leads to. Any chapter of the same story, including the current one. |
| Libellé | The button text written by the author. When empty, the target chapter's current title is shown. |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Visible choices, on chapters they can already read | Click a choice |
| `user` (non-confirmed) | Same as guest | Click a choice; edit only if already an author (no new rule) |
| `user-confirmed` | Same as guest | Click a choice |
| Author / co-author of the story | All choices, including those whose target is unpublished (marked « non publié ») | Add, edit, reorder, remove choice blocks in Avancé mode |
| Moderator | Same as the reader view they already have | No new action |
| Admin | Same as moderator | No new action |

Reading visibility of the block follows the chapter's own visibility — no new
rule (A3).

## 4. Functional requirements

### 4.1 Author adds a choice block

1. An author or co-author edits a chapter in Avancé mode.
2. The block palette and the « + » insert menu offer « Choix de chapitres »
   next to text and image. News and static-page editors never offer it (A1).
3. The block starts with one empty choice. The author can add, remove and
   reorder choices inside the block.
4. For each choice, the author picks the target from a dropdown listing
   **every chapter of the story**, in reading order, drafts included (marked as
   unpublished) and the current chapter included (loops are allowed).
5. The author optionally types a label (plain text, ~120 characters max — A9).
   Left empty, the button shows the target chapter's title.
6. The block can be placed anywhere and moved up/down like an image block.
7. On save, a choice with no target is dropped; a block left with no choice is
   dropped (A4).

### 4.2 Reader reads a chapter with choices

1. The block renders as a group of buttons, stacked/wrapping on small screens (A8).
2. Clicking a button opens the target chapter.
3. A choice whose target is not published, or no longer exists, is **hidden**
   from readers. If every choice of a block is hidden, the whole block is hidden.
4. Authors/co-authors see all choices; those with an unpublished target carry a
   « non publié » marker.
5. Button labels cannot be quoted into the quote book, and do not count in the
   chapter's word/character counts (A5).
6. The chapter's linear previous/next navigation is unchanged.

### 4.3 Target chapter changes

1. **Renamed** — the choice still leads to it; a choice without a custom label
   shows the new title.
2. **Reordered** — nothing changes; choices target a chapter, not a position.
3. **Unpublished / scheduled not yet published** — hidden from readers, shown
   to authors with « non publié »; reappears for readers once published.
4. **Deleted** — hidden from readers. When an author next edits the chapter
   holding the choice, the editor flags it « Chapitre supprimé » so it can be
   repointed or removed. Saving is still allowed with the flagged choice kept.

### 4.4 Mode switching

1. Switching a chapter back to Simple mode is refused while it contains a
   choice block, as it already is for images (A2).

## 5. Lifecycle

- **Target chapter deleted** — see 4.3.4. The choice survives in the source
  chapter until the author acts; readers never see it.
- **Chapter holding the block deleted** — the block goes with it; nothing points
  at it (choices are one-way).
- **Story deleted** — all its chapters, blocks included, go with it.
- **Author deactivated / deleted** — no feature-specific data; existing story
  rules apply.
- **Moderation « empty content »** — clears the whole chapter content, blocks
  included (existing behaviour).

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Whoever may edit the chapter may use the block; everyone who may read it sees visible choices (A3). |
| Visibility / privacy | Readers never see a choice leading to a chapter they cannot open (#1). |
| Settings | N/A — no user preference. |
| Notifications | N/A — nobody is notified (A6). |
| Domain events | N/A — no new event (A6). DESIGN decides whether reacting to chapter deletion/unpublish is needed technically. |
| Statistics | N/A (A6). |
| Moderation | N/A — moderators already see chapter content; no new report topic (A6). |
| Lifecycle / cascade | See §5. |
| Media | N/A — no image. |
| Search | N/A — labels not indexed (A6). |
| i18n | French only: « Choix de chapitres », « Ajouter un choix », « Libellé (facultatif) », « Chapitre cible », « non publié », « Chapitre supprimé ». |
| Mobile | Buttons wrap and stack on small viewports (A8). |
| Accessibility | Choices are real links with their label as accessible name; editor controls keyboard-operable like existing blocks. |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | A choice's target is unpublished or deleted — what does a reader see? | Hidden for readers; authors/co-authors see it marked « non publié ». |
| 2 | What text does the button show? | Custom label, falling back to the target chapter's title when empty. |
| 3 | One button per block, or a group? | A group of 1..N choices per block; hidden choices drop out, an all-hidden block disappears. |
| 4 | Target hard-deleted — what does the author see in the editor? | Choice flagged « Chapitre supprimé », kept until the author fixes it; save allowed. |
| 5 | Touch the linear previous/next navigation? | No — unchanged; out of scope. |
| 6 | Can readers quote button labels? | No — excluded from quoting. |
| 7 | Replay and assumptions A1–A9 | Accepted as replayed. |

## 8. Out of scope

- Offering the block anywhere other than story chapters (news, static pages, FAQ).
- Hiding or changing the linear previous/next chapter navigation.
- Links to chapters of other stories, or to external URLs.
- Rewriting or converting existing plain inline links into choice blocks.
- A story-level "interactive story" flag, branch map, or visualisation of the chapter graph.
- Tracking which choice a reader made, or per-reader branch state.
- Choice blocks in Simple mode or in the chapter's author note.
- Notifications, statistics, search indexing of labels.

## 9. Open questions

- *(non-blocking, DESIGN)* How the editor gains a pluggable, consumer-scoped
  block type (the Editor's block list is hardcoded today) — the user's
  architectural constraint from `00-request.md`.
- *(non-blocking, DESIGN)* Decisions #1/#2 need information only known at read
  time (target published? current title?), while chapter HTML is rendered and
  stored at save time — DESIGN must reconcile the two.
- *(non-blocking, DESIGN)* How button labels are kept out of quote anchoring
  without disturbing existing quotes.
