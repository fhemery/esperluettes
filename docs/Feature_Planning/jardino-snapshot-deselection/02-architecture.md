# Jardino — snapshot deselection — architecture

> DESIGN output (auto mode). Shape and contracts only; the file list is in
> `03-plan.md`. Judgement calls are recorded in `DECISIONS.md` (A5–A7).

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Calendar domain, Jardino activity sub-module
(`app/Domains/Calendar/Private/Activities/Jardino/`). Everything touched is
private to that sub-module.

### 1.1 Changes in other domains

None.

## 2. Data model

### 2.1 Tables

`calendar_jardino_story_snapshots` — **drop** `deselected_at` (nullable
timestamp) via a **new** migration in the Jardino migrations folder. The
original create migration is not edited (it has run in production).
`down()` re-adds it as `timestamp('deselected_at')->nullable()` after
`selected_at`.

No new index. The (goal_id, story_id) uniqueness invariant is enforced in the
service, not by a unique index (A6).

### 2.2 Model

- `JardinoStorySnapshot`: remove `deselected_at` from `#[Fillable]` and from
  `$casts`; remove `isActive()`.
- `JardinoGoal`: remove the `currentStorySnapshot` relation. `storySnapshots`
  (hasMany) stays.

### 2.3 Lifecycle rules

Unchanged — snapshots cascade with their goal (existing FK).

## 3. PHP architecture

### 3.1 Public API

None — nothing in the change is public.

### 3.2 Services

- `JardinoGoalService::createInitialSnapshot` — look up the snapshot by
  `(goal_id, story_id)` only (drop the `whereNull('deselected_at')` filter). If
  found, return it untouched (resume); otherwise create it as today. Behaviour
  is identical to today since the filter never excluded anything, but the rule
  now reads "one snapshot per (goal, story)".
- `JardinoProgressService::updateSnapshotWordCount` — for each goal tracking
  the story, load its snapshot via `storySnapshots` constrained by
  `story_id = $storyId` instead of `currentStorySnapshot`. Delta and
  `biggest_word_count` logic unchanged.
- `calculateTotalWordsWritten`, `calculateProgressPercentage`,
  `JardinoFlowerService` — unchanged.

### 3.3 Policy / authorization

Unchanged.

### 3.4 Events and listeners

Unchanged (`UpdateSnapshotWordCount` on ChapterCreated/Updated/Deleted).

### 3.5 Routes, controllers, form requests

Unchanged.

## 4. Frontend

None.

## 5. Deptrac impact

None — no new cross-domain edge.

## 6. Tests

Feature tests in `app/Domains/Calendar/Tests/Feature/Jardino/`, driven through
the existing HTTP helpers (`createOrUpdateGoal`) and chapter events:

1. Switch A → B → A: exactly one snapshot per story for the goal (2 rows, not 3),
   and the A snapshot's `initial_word_count` is the one from the first
   selection.
2. Total progress across switches: words written on A, switch to B, words on
   B, switch back to A, words on A → total = sum of all three writes.
3. Word deltas after switching land only on the tracked story's snapshot.
4. Schema: `deselected_at` no longer exists on the table (guards the migration).

## 7. Tradeoffs locked

| # | Tradeoff | Chosen | Rejected |
|---|----------|--------|----------|
| 1 | Write vs delete the flag | Delete (A1) | Write `deselected_at` on switch, clear on re-select — maintains state nobody reads |
| 2 | Uniqueness of (goal, story) | Service check + test (A6) | Unique index — would fail the migration on any pre-existing duplicate row, and a duplicate needs a concurrent double-save to happen; cheap to add later |
| 3 | Migration style | New drop-column migration (A5) | Editing the create migration — already applied in production |

## 8. Documentation

The Jardino activity README (`J/README.md`, "Not done" §) mentions the unwritten
column — remove that item and describe the (goal, story) invariant (`document-activity`
at WRAP).
