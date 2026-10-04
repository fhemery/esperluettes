# Dashboard — story to discover: exclude already-read — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Exclude stories with a reading mark from the discover query | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

There is one phase only, and no checkpoint. The change adds one `WHERE` clause
to a query with a single caller and reshapes no existing code (architecture
§1, §3.2, §8). No refactor phase is needed.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Exclude stories with a reading mark from the discover query

**Goal.** `StoryRepository::getRandomStories()` stops returning any story for
which the viewer has at least one `story_reading_progress` row. The filter is
unconditional, with no new parameter (architecture §2.1, §2.3, §3.2, §7).

**Context.** The « Histoires à découvrir » dashboard block is Story's
`<x-story::random-stories-component />`
(`Private/View/Components/RandomStoriesComponent.php`). It calls
`StoryService::getRandomStories($userId, 7, $visibilities)`, which delegates to
`StoryRepository::getRandomStories($viewerId, $nbStories, $visibilities,
$noTwOnly)`. That query already excludes authored stories, stories with no
published chapter, wrong visibilities and (optionally) trigger-warned stories.
Reading marks are rows in `story_reading_progress` (`ReadingProgress` model,
columns `user_id`, `story_id`, `chapter_id`), indexed on `(user_id, story_id)`.
The Blade view already renders the « Découvrir plus » placeholder last,
whatever the number of stories, so the "fewer than 7" and "none" cases need no
view change.

**Deliverables.**
- `app/Domains/Story/Private/Repositories/StoryRepository.php` — in
  `getRandomStories()`, add one filter next to the existing ones, before
  `inRandomOrder()`. Use a `whereNotExists` subquery on
  `story_reading_progress` (no new relation on `Story`; the architecture leaves
  this choice to PLAN, and the subquery adds nothing to the model):
  ```php
  // Exclude stories the viewer has started (≥1 chapter marked as read)
  $query->whereNotExists(function ($q) use ($viewerId) {
      $q->selectRaw('1')
          ->from('story_reading_progress')
          ->where('story_reading_progress.user_id', $viewerId)
          ->whereColumn('story_reading_progress.story_id', 'stories.id');
  });
  ```
  Use `(new ReadingProgress())->getTable()` instead of the literal if that is
  the file's convention. Any progress row counts, published chapter or not
  (architecture §7 #1). Do not join `story_chapters`.
- `app/Domains/Story/Private/Services/StoryService.php` — docblock of
  `getRandomStories()` only: add "Excludes stories the viewer has started (at
  least one chapter marked as read)." Signature unchanged.
- `app/Domains/Story/Tests/Feature/Stories/Components/RandomStoriesComponentTest.php`
  — the new tests below, in the existing `describe('RandomStoriesComponent')`.

No migration, no model change, no Blade, no translation, no public API, no
deptrac edge (architecture §2, §3.1, §4, §5).

**Tests.** All in `RandomStoriesComponentTest.php`. They follow the file's
existing pattern: `alice($this)` authors public stories with
`publicStory()` + `createPublishedChapter()`, the viewer is
`bob($this, roles: [Roles::USER])`, `$this->actingAs($viewer)`, then
`Blade::render('<x-story::random-stories-component />')`. Marks are set
through the real endpoints with the global helpers `markAsRead($this, $chapter)`
/ `markAsUnread($this, $chapter)` (`Story/Tests/helpers.php`), while acting as
the user who reads. Write them failing first.

- `it('excludes stories in which the viewer has marked a chapter as read')` —
  two public stories, viewer marks C1 of one: that one is absent, the other is
  present. Use a story with **two** published chapters and mark only one, so
  the test also proves "≥1 chapter" rather than "all chapters".
- `it('still shows a story another user has marked as read')` — act as a third
  user (e.g. `carol`), mark a chapter read, then act as the viewer: the story
  is present.
- `it('still shows a story in the viewer read list with no chapter read')` —
  viewer calls `addToReadList($this, $story->id)` (ReadList's global test
  helper, already used by Story's `StoryShowTest`), marks nothing: the story is
  present (decision #1).
- `it('shows a story again once the viewer unmarks every chapter')` — viewer
  marks then unmarks the only chapter: the story is present.
- `it('renders only the placeholder when every eligible story has been read')`
  — one public story, viewer marks its chapter: the HTML contains
  `__('story::discover.placeholder_cta')` and not the story title
  (decision #2).

The existing tests in this file must stay green unchanged.

**Acceptance.**
- ✅ A viewer with a reading mark on any chapter of a story no longer sees that
  story in `<x-story::random-stories-component />`.
- ✅ A mark by another user does not hide the story for the viewer.
- ✅ A story in the viewer's pile à lire with no chapter read is still shown.
- ✅ Unmarking every chapter makes the story eligible again.
- ✅ With no eligible story left, only the « Découvrir plus » placeholder renders.
- ✅ `StoryService::getRandomStories()` and `StoryRepository::getRandomStories()`
  signatures are unchanged.
- ✅ All pre-existing `RandomStoriesComponentTest` tests pass unchanged.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. Layout is unchanged (architecture §4,
§6), so this stays short.

| Surface | Check | OK? |
|---------|-------|-----|
| Dashboard, `user-confirmed` reader who has read ≥1 chapter of some stories (desktop) | « Histoires à découvrir » shows no story the reader has started; placeholder last | |
| Same page after reloading a few times | Started stories never appear; un-started ones (including pile à lire ones with no chapter read) still do | |
| Dashboard, reader who has started every eligible story | Block shows only the « Découvrir plus » placeholder, no broken layout | |
| Dashboard, mobile width, reader with fewer than 7 eligible stories | Carousel holds fewer slides, placeholder last, no layout gap | |
| Story page: unmark the only read chapter, back to dashboard | The story can appear again in the block | |

## Open items

None. Verified during PLAN:
- `StoryRepository::getRandomStories()` has a single caller chain
  (`RandomStoriesComponent` → `StoryService`), so the unconditional filter
  affects only the dashboard block (phase 1).
- `markAsRead` / `markAsUnread` (Story) and `addToReadList` (ReadList) test
  helpers exist and are loaded globally by `tests/Pest.php` (phase 1).
- Neither `app/Domains/Story/README.md` nor `app/Domains/Dashboard/README.md`
  lists the discover selection rules, so no domain doc needs updating
  (phase 1).
