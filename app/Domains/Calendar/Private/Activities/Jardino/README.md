# Jardino

A word-count writing challenge. A participant sets a target word count and picks
**one** of their stories to track. As they write, the words they add to that
story count towards the target and earn *flowers*, which they plant on a shared
pixel-art garden map that everyone taking part can see.

This is a Calendar activity plugin. The generic parts — activity states, role
restrictions, the registry — are documented in the
[Calendar README](../../../README.md).

## How it plugs in

- `JardinoRegistration` (type key `jardino`) exposes the display component
  `jardino::jardino-component`; no config component.
- `JardinoServiceProvider` loads the activity's own views (`jardino::`),
  translations, migrations and routes, and subscribes the listeners below.

## Events it listens to

`UpdateSnapshotWordCount` subscribes to three Story events on the `EventBus`:

| Event | Word delta applied |
|---|---|
| `Story::ChapterCreated` | `+ chapter.wordCount` |
| `Story::ChapterUpdated` | `after.wordCount - before.wordCount` |
| `Story::ChapterDeleted` | `- chapter.wordCount` |

The delta is applied to the *active snapshot of the goal whose `story_id`
matches the event's story*. A story that is not the current target of any goal
produces no write. Chapter events are the only source of progress — nothing
polls or recomputes word counts.

## Tables

| Table | Holds |
|---|---|
| `calendar_jardino_goals` | One row per (activity, user): target word count and the currently tracked `story_id`. Unique on `(activity_id, user_id)`. |
| `calendar_jardino_story_snapshots` | Per goal and story: the word count when the story was selected (`initial`), the running count (`current`) and the high-water mark (`biggest`). |
| `calendar_jardino_garden_cells` | Sparse grid: one row per planted flower or admin-blocked cell. Unique on `(activity_id, x, y)`. Empty cells have no row. |

## Rules a reader would get wrong

**Progress accumulates across story switches.** Words written = the sum of
`current - initial` over *all* the goal's snapshots, not just the current one.
Switching the tracked story therefore keeps everything already earned and starts
a fresh snapshot for the new story. Re-selecting a story used earlier resumes its
existing snapshot rather than resetting it.

**Flowers are computed from `biggest_word_count`, progress from
`current_word_count`.** `biggest` never decreases, so deleting a chapter lowers
the displayed progress but never takes an earned flower back.

**Three caps apply to earned flowers, and the smallest wins:**
1. one flower per 5% of the target reached (so 20 at 100%),
2. two flowers per elapsed day since `active_starts_at`, counted in **CET** with
   the start day counting as day 1,
3. an absolute maximum of 25 (reached at 125% of the target).

*Available* flowers are the earned count minus those already planted.

**Planting requires an ACTIVE activity and an existing goal.** The state check
lives in `JardinoFlowerController`; the garden stays readable in every other
visible state. A user with no goal sees the garden but cannot plant.

**Blocked cells are admin-only and have a wider window.** Admins and tech-admins
may block or unblock a cell from `preview_starts_at` until `active_ends_at` —
i.e. before the activity opens, unlike planting.

**The grid is fixed in code**, not per activity: `GardenMapConstants` defines
70x53 cells and 28 flower sprites under `public/images/activities/jardino/`.
Changing the grid after an activity has started would strand already-planted
cells outside the new bounds.

## Accepted behaviour

- **No cleanup on user deletion.** Nothing listens to `Auth::UserDeleted`, so a
  deleted user's goals, snapshots and planted flowers stay in place, and those
  flowers render without an owner name. This is deliberate: a garden is a shared
  artefact of the challenge, and removing one participant's flowers would punch
  holes in everyone else's map. Do not "fix" it without deciding what the map
  should look like afterwards.

## Not done

- **`deselected_at` is never written.** The column and
  `JardinoStorySnapshot::isActive()` exist, but nothing marks a snapshot as
  deselected when the goal switches story. Progress stays correct only because
  `JardinoProgressService::updateSnapshotWordCount()` constrains the eager-loaded
  snapshot by `story_id`; `JardinoGoal::currentStorySnapshot` on its own can
  return the wrong row once a goal has tracked more than one story. Fix the write
  side before relying on that relation.
- Goals can be created and updated but never deleted; there is no way to leave
  the challenge.
