# Jardino — `deselected_at` is never written — request

Found on 2026-07-28 while documenting the activity from its code.

## The problem

`calendar_jardino_story_snapshots.deselected_at` exists, `JardinoStorySnapshot::isActive()`
reads it, and `JardinoGoal::currentStorySnapshot` is a `hasOne` filtered on
`whereNull('deselected_at')` — but **nothing ever writes the column**.
`JardinoGoalService` does not mark the previous snapshot deselected when a
participant switches the story their goal tracks.

So once a goal has tracked two stories, every snapshot is still "active" and
`currentStorySnapshot` returns whichever the database hands back first.

## Why it does not currently break

`JardinoProgressService::updateSnapshotWordCount()` re-constrains the eager-loaded
snapshot by `story_id`, so word deltas still land on the right row. The bug is
latent: the relation is wrong, and the next caller to trust it gets the wrong
snapshot.

Total progress is unaffected either way — it sums `current - initial` across
**all** the goal's snapshots by design, so switching stories keeps what was
already earned.

## What to decide at REFINE

Two coherent fixes, and they are not equivalent:

1. **Write the column** — set `deselected_at` on the previous snapshot when the
   tracked story changes, and clear it when a story is re-selected. Keeps the
   relation meaningful and the history readable.
2. **Delete the column** — if nothing needs to know which snapshot is current
   (progress does not), drop `deselected_at`, `isActive()` and
   `currentStorySnapshot` rather than maintaining state nobody reads.

Prefer whichever leaves less machinery. Do not do half of either.

## Acceptance

- A goal that has tracked two or more stories has exactly one active snapshot,
  or the concept is gone entirely.
- Re-selecting a previously tracked story resumes its snapshot rather than
  creating a second one for the same story — verify this holds after the fix.
- Total progress across story switches is unchanged by the fix. Test it.
