# Jardino — snapshot deselection — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Drop `deselected_at`, look snapshots up by (goal, story) | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

One phase only (A8): the migration, the model clean-up and the two service
edits are interdependent — removing the column without the service edits
breaks `createInitialSnapshot`, and removing `currentStorySnapshot` without
the progress-service edit breaks word-count updates. Splitting would not leave
a green gate in between.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Drop `deselected_at`, look snapshots up by (goal, story)

**Goal.** Remove the never-written `deselected_at` concept (column, cast,
`isActive()`, `currentStorySnapshot`) and make every snapshot lookup key on
(goal_id, story_id), keeping progress across story switches unchanged.

Needs: `02-architecture.md` §2.1, §2.2, §3.2, §6. Behaviour to preserve:
`01-functional.md` §4.2–§4.5. No earlier phase.

All paths below are relative to
`app/Domains/Calendar/Private/Activities/Jardino/` unless they start with
`app/`.

**Deliverables.**

- **New migration**
  `Database/Migrations/2026_09_28_120000_drop_deselected_at_from_calendar_jardino_story_snapshots_table.php`
  (picked up by the existing `loadMigrationsFrom` in `JardinoServiceProvider`).
  - `up()`: `Schema::table('calendar_jardino_story_snapshots', fn (Blueprint $t) => $t->dropColumn('deselected_at'))`.
  - `down()`: `$t->timestamp('deselected_at')->nullable()->after('selected_at')`.
  - Do **not** edit `2025_10_23_000001_create_calendar_jardino_story_snapshots_table.php`
    (applied in production — A5).
- **`Models/JardinoStorySnapshot.php`**: remove `'deselected_at'` from
  `#[Fillable]` and from `$casts`; delete `isActive()` and its docblock.
- **`Models/JardinoGoal.php`**: delete `currentStorySnapshot()` and the now
  unused `use Illuminate\Database\Eloquent\Relations\HasOne;`. `storySnapshots()`
  stays.
- **`Services/JardinoGoalService.php`** — `createInitialSnapshot()`: drop the
  `->whereNull('deselected_at')` line; update the comments to say "a snapshot
  for this (goal, story) already exists → resume it untouched". Creation branch
  unchanged.
- **`Services/JardinoProgressService.php`** — `updateSnapshotWordCount()`:
  eager-load `storySnapshots` constrained by `story_id = $storyId` instead of
  `currentStorySnapshot`, and take `$goal->storySnapshots->first()` (it is a
  collection; the service invariant guarantees at most one row). Delta and
  `biggest_word_count` logic unchanged. Rename the local `$currentSnapshot` to
  `$snapshot`.
- No other file. The Jardino `README.md` "Not done" item about `deselected_at`
  is rewritten at WRAP (`02-architecture.md` §8), not in this phase.

**Tests.** New file
`app/Domains/Calendar/Tests/Feature/Jardino/JardinoStorySwitchTest.php`, same
setup as `JardinoProgressEventsTest.php` (`registerFakeActivityType`, `alice`,
`admin`, `createActiveJardino`, `createGoal`, `publicStory` for stories A and
B owned by alice, `dispatchChapterCreated`, `getJardinoObjectiveViewModel`,
`updateActivityStartDate` where flowers matter). Stories and goal switches go
through `createGoal(...)` (i.e. `JardinoGoalService::createOrUpdateGoal`, the
path the dashboard controller uses). Snapshot rows are read with
`JardinoStorySnapshot::query()->where('goal_id', …)`.

`describe('Jardino story switching', …)`:

1. `it('keeps one snapshot per story when switching A → B → A')` — after the
   three selections the goal has exactly 2 snapshot rows (one for A, one for
   B); the A row has the same `id` and `initial_word_count` as right after the
   first selection.
2. `it('counts progress from every story across switches')` — 1500 words on A,
   switch to B, 1000 words on B, switch back to A, 500 words on A →
   `wordsWritten === 3000`.
3. `it('applies word deltas only to the tracked story snapshot')` — after
   switching from A to B, a `ChapterCreated` on A leaves A's snapshot
   `current_word_count` unchanged and a `ChapterCreated` on B increases only
   B's.
4. `it('no longer has a deselected_at column')` —
   `Schema::hasColumn('calendar_jardino_story_snapshots', 'deselected_at')` is
   `false`.

Write these first; with the current code, test 4 fails (column exists) and
tests 1–3 should already pass (the filter never excluded anything). They are
the regression net for the refactor. Existing `JardinoProgressEventsTest`,
`FlowerPlantingTest`, `JardinoSaveGoalTest`, `JardinoComponentTest` must stay
green unchanged.

**Acceptance.**
- ✅ `grep -rn "deselected_at\|currentStorySnapshot\|isActive()" app/Domains/Calendar/Private/Activities/Jardino --include=*.php`
  matches only the untouched create migration and the new drop migration.
- ✅ Switching A → B → A leaves exactly 2 snapshot rows for the goal, and A's
  row keeps its original `id` and `initial_word_count`.
- ✅ Total words written after A(1500) → B(1000) → A(500) is 3000.
- ✅ A chapter event on a story the goal no longer tracks changes no snapshot.
- ✅ `php artisan migrate:rollback --step=1` then `migrate` works on the new
  migration (re-adds then drops the nullable column) — checked once by hand in
  sail, or trusted to `down()` review if the local DB is not available.
- ✅ `./vendor/bin/sail artisan test --filter=Jardino` green.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

No UI, route, view or string changes (`01-functional.md` §1 "No user-visible
change"; `02-architecture.md` §4 "Frontend: None"). **VERIFY is N/A** — the
feature tests above are the evidence. One optional smoke row if VERIFY runs
anyway:

| Surface | Check | OK? |
|---------|-------|-----|
| Jardino dashboard, participant with a goal | Page renders; switching the tracked story and saving still shows the objective with the same words-written total as before the switch | N/A (no UI change) |

## Open items

- None blocking. Verified during PLAN: the only readers/writers of
  `deselected_at`, `isActive()` and `currentStorySnapshot` are the five files
  listed in Phase 1 (plus the create migration); `JardinoServiceProvider`
  loads the Jardino migrations folder, so the new migration needs no
  registration.
- Non-blocking, carried from `01-functional.md` §9 (A3): words written on a
  story while it was not tracked are never counted after re-selection. Kept as
  today's behaviour; surface at WRAP.
