# Dashboard — story to discover: exclude already-read

**Status:** DONE — 2026-10-04 · **Domain(s):** `Story`

## What it does

The dashboard block « Histoires à découvrir » no longer offers a story the
viewer has already started. A story is "started" when the viewer has at least
one `story_reading_progress` row for it. One `whereNotExists` subquery in the
discover query; no new setting, relation, migration or UI change.

## Key behaviour

- Excluded: any story with ≥1 reading-progress row for the viewer, whatever the
  chapter's status (published or not).
- Kept: a story in the pile à lire with no chapter read; a story another user
  has read.
- Unmarking every chapter makes the story eligible again.
- Same rule for `user` and `user-confirmed`; no setting to turn it off.
- Fewer than 7 eligible stories: the carousel is shorter, « Découvrir plus »
  placeholder still last (alone if nothing is eligible). Never topped up with
  read stories.
- Counter-intuitive: the filter is unconditional because the dashboard block is
  the only caller of `getRandomStories()`. A second caller wanting read stories
  would need a flag.

## Where the code lives

| Concern | Path |
|---------|------|
| Filter | `app/Domains/Story/Private/Repositories/StoryRepository.php` (`getRandomStories()`) |
| Service | `app/Domains/Story/Private/Services/StoryService.php` (docblock only) |
| Component | `app/Domains/Story/Private/View/Components/RandomStoriesComponent.php` (unchanged) |
| Tests | `app/Domains/Story/Tests/Feature/Stories/Components/RandomStoriesComponentTest.php` |
| Migrations | none — `(user_id, story_id)` on `story_reading_progress` already indexed |

## Extension points used

None.

## Decisions worth remembering

- "Read" means any reading-progress row, not "all chapters read" (D1, D4).
- Filter lives in the repository, unconditional (D5) — no flag, no new Story
  relation.
- Pile à lire stories not yet started stay eligible (D1).

## Not done

- Non-goals: filtering read stories out of library/search/other surfaces; a user
  setting to disable the filter; topping up with read stories; wording,
  placeholder or layout changes; "read" tracking for guests or on chapter view.
- Cut mid-build: nothing. No e2e spec was left in `e2e/tests/features/`, so
  nothing to retire.
- Backlog rows created: none.
