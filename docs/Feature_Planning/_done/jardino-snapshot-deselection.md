# Jardino — drop `deselected_at`, one snapshot per (goal, story)

**Status:** DONE — 2026-09-28 · **Domain(s):** `Calendar` (Jardino activity)

## What it does

`calendar_jardino_story_snapshots.deselected_at` existed and was read
(`JardinoStorySnapshot::isActive()`, `JardinoGoal::currentStorySnapshot`) but
never written, so the "current snapshot" relation was meaningless once a goal
had tracked two stories. The concept is **removed** rather than maintained: the
goal's `story_id` is the only source of truth for the tracked story, and every
snapshot lookup keys on (goal_id, story_id). No user-visible change.

## Key behaviour

- One snapshot per (goal, story); enforced by the service check-then-create, no unique index.
- Re-selecting a previously tracked story resumes its snapshot untouched (same row, same `initial`).
- Chapter deltas land only on the snapshot of the goal's currently tracked story.
- Total progress = Σ `current − initial` over all the goal's snapshots — unchanged.
- Words written on a story while untracked are never counted after re-selection (kept, see A3).

## Where the code lives

All under `app/Domains/Calendar/Private/Activities/Jardino/` unless stated.

| Concern | Path |
|---------|------|
| Migration (drop column; `down()` re-adds nullable after `selected_at`) | `Database/Migrations/2026_09_28_120000_drop_deselected_at_from_calendar_jardino_story_snapshots_table.php` |
| Models (`isActive()`, `currentStorySnapshot`, cast/fillable removed) | `Models/JardinoStorySnapshot.php`, `Models/JardinoGoal.php` |
| Snapshot creation / resume | `Services/JardinoGoalService.php` → `createInitialSnapshot()` |
| Word-count updates (eager-loads `storySnapshots` filtered by `story_id`) | `Services/JardinoProgressService.php` → `updateSnapshotWordCount()` |
| Tests | `app/Domains/Calendar/Tests/Feature/Jardino/JardinoStorySwitchTest.php` |
| Activity doc | `README.md` (rule "One snapshot per (goal, story)") |

The create migration `2025_10_23_000001_…` is untouched (applied in production).
Code matches the plan; no drift.

## Extension points used

None.

## Decisions worth remembering

No question was put to the user (auto mode). Assumptions, all reversible
unless noted:

- **A1** Delete the flag (column, `isActive()`, `currentStorySnapshot`) rather than write it — nobody needs a "current" snapshot.
- **A2** Re-selection resumes the snapshot (no `initial` reset, no second row).
- **A3 — worth revisiting.** Words written on a story while it was not tracked are not counted when it is re-selected: the resumed `current` is stale and later events add only their own delta. Kept as pre-existing behaviour.
- **A4** Dropping the column loses nothing — never written, all NULL (N/A).
- **A5** New drop migration; create migration left alone.
- **A6** No unique index on (goal_id, story_id) — it could fail the migration on a pre-existing duplicate. Cheap to add later.
- **A7** `updateSnapshotWordCount` uses `storySnapshots` filtered by `story_id`, taking `->first()`.
- **A8** Single BUILD phase; VERIFY skipped as N/A (no UI change).
- **A9** Migration round trip (`migrate` → `rollback --step=1` → `migrate`) checked by hand on local sail DB (N/A).

## Not done

- Non-goals: any change to progress/flower computation; back-filling words written on untracked stories; any UI for snapshot history.
- Nothing cut mid-build.
- Open (non-blocking): A3 — the user may want untracked writing counted on re-selection (would need a resync of `current` from the story's word count when resuming). Not pushed to the backlog; the user decides.
- No e2e spec was written, none to retire.
