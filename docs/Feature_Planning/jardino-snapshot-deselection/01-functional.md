# Jardino — snapshot deselection — functional specification

> REFINE output (auto mode). Describes **what** the fix does, never **how** it
> is built. Every judgement call is in the assumptions table of `DECISIONS.md`.

## 1. Overview

A Jardino goal tracks one story at a time, and keeps one word-count snapshot
per story it has ever tracked. The snapshot table carries a `deselected_at`
column meant to mark which snapshot is "current", but nothing writes it, so the
"current snapshot" relation is meaningless once a goal has tracked two stories.

The fix **removes the concept** rather than maintaining it: the goal's own
`story_id` already says which story is current, and no progress, flower, UI or
admin computation needs a "current" snapshot. What remains is the invariant
that matters — **one snapshot per (goal, story)** — and the existing behaviour
that progress across story switches is kept.

No user-visible change.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Objectif (goal) | A participant's Jardino target for one activity; tracks exactly one story at a time (`story_id` on the goal). |
| Snapshot | Per (goal, story) record of the story's word count when first tracked (`initial`), now (`current`) and at its peak (`biggest`). |
| Histoire suivie | The story the goal currently tracks — the goal's `story_id`, and only that. |

## 3. Roles & visibility

Unchanged. Participants see their own Jardino progress; nothing about
snapshots is exposed to any role.

## 4. Functional requirements

### 4.1 Choosing the tracked story for the first time

1. The participant saves a goal with story A.
2. A snapshot for (goal, A) is created with `initial = current = biggest =`
   A's word count at that moment.

### 4.2 Switching to another story

1. The participant changes the goal's story from A to B.
2. If no snapshot exists for (goal, B), one is created from B's current word
   count. A's snapshot is left untouched (it no longer receives word deltas,
   since the goal no longer tracks A).
3. Total progress still includes what was earned on A.

### 4.3 Switching back to a previously tracked story

1. The participant changes the goal's story from B back to A.
2. The existing (goal, A) snapshot is **resumed**: no second snapshot for A is
   created, and its `initial_word_count` is not reset. Words written on A while
   it was not tracked (between the switches) are counted when the next chapter
   event on A updates the snapshot — see §9 note.

### 4.4 Word-count updates

1. A chapter of story S is created, updated or deleted.
2. For every goal (of an active activity) whose tracked story is S, the delta
   is applied to that goal's (goal, S) snapshot — found by goal and story, not
   by a "current" flag. Other snapshots of that goal are untouched.

### 4.5 Total progress and flowers

Unchanged by the fix: total words = Σ over **all** the goal's snapshots of
`current − initial` (clamped ≥ 0); flowers from Σ `biggest − initial`.

## 5. Lifecycle

Unchanged. Snapshots cascade-delete with their goal. Existing data: all rows
already have `deselected_at = NULL` (the column was never written), so dropping
it loses nothing.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no permission change |
| Visibility / privacy | N/A — snapshots are never displayed individually |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A — existing chapter listeners unchanged in behaviour |
| Statistics | N/A — no statistic reads snapshots |
| Moderation | N/A |
| Lifecycle / cascade | Unchanged (goal cascade) |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no user-facing string |
| Mobile | N/A |
| Accessibility | N/A |

## 7. Decisions confirmed

No question put to the user (auto mode). Judgement calls: see
`DECISIONS.md` → "Assumptions made without asking" (A1–A4).

## 8. Out of scope

- Any change to how progress or flowers are computed.
- Back-filling or reconciling words written on a story while it was not
  tracked (see §9).
- Any UI showing snapshot history.
- Other Jardino "Not done" items from the activity README.

## 9. Open questions

- **Non-blocking** — Words written on story A while the goal tracked B: when A
  is re-selected, the resumed snapshot's `current_word_count` is stale until the
  next chapter event on A, and that event applies only its own delta — so words
  written on A in between are never counted. This is today's behaviour and the
  request asks to keep it ("resumes its snapshot"); flagged for the user, not
  changed.
