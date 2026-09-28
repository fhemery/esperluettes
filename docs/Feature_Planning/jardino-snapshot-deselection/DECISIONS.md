# Jardino — snapshot deselection — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Fix option 2 — **delete** `deselected_at`, `isActive()` and `currentStorySnapshot`. Reason: the goal's `story_id` is already the source of truth for the tracked story; no caller (UI, progress, flowers, admin) needs a "current" snapshot; the only reader re-filters by `story_id` anyway. Less machinery than maintaining the flag. | REFINE 2026-09-28 | Yes — re-add column + writes |
| A2 | Re-selecting a previously tracked story resumes its snapshot unchanged (no reset of `initial`, no second row). Matches the request's acceptance. | REFINE 2026-09-28 | Yes |
| A3 | Words written on a story while it was not tracked are not counted on re-selection (today's behaviour, kept). | REFINE 2026-09-28 | Yes |
| A4 | Dropping the column loses no data: it was never written, so every row holds NULL. | REFINE 2026-09-28 | N/A |
| A5 | Drop the column in a new migration; the create migration is left untouched (already applied in production). | DESIGN 2026-09-28 | Yes — `down()` re-adds it |
| A6 | No unique index on (goal_id, story_id): the service check-then-create is kept and tested. A unique index could fail the migration on a pre-existing duplicate. | DESIGN 2026-09-28 | Yes — add index later |
| A7 | `updateSnapshotWordCount` loads the snapshot through `storySnapshots` filtered by `story_id`, replacing `currentStorySnapshot`. | DESIGN 2026-09-28 | Yes |
| A8 | Single BUILD phase; VERIFY is N/A (no UI change). Migration, model and service edits cannot be split and each stay green. Jardino README clean-up left to WRAP per architecture §8. | PLAN 2026-09-28 | Yes |
