# Dashboard — story to discover: exclude already-read — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Story** owns it entirely. The « Histoires à découvrir » block is Story's
`<x-story::random-stories-component />`, which Dashboard only embeds. Its
selection lives in `StoryRepository::getRandomStories()`, reached through
`StoryService::getRandomStories()`. Reading marks are Story's own
`story_reading_progress` table. The new rule is one more filter in that query,
next to the existing ones: authored stories, published chapter, visibility,
and the trigger-warning preference.

### 1.1 Changes in other domains

None. Dashboard's view keeps embedding the component unchanged. No public API
changes.

## 2. Data model

### 2.1 Tables

No change. `story_reading_progress` already carries `story_id` alongside
`chapter_id`, with an index on `(user_id, story_id)`. The migration comment
says that index exists "for dashboards/filters", so the new filter is a
correlated `NOT EXISTS` on that index:

```
NOT EXISTS (SELECT 1 FROM story_reading_progress
            WHERE user_id = :viewer AND story_id = stories.id)
```

### 2.2 Model

No change. Express the filter either through a `whereNotExists` subquery on
`ReadingProgress`, or through a `readingProgress` `HasMany` on `Story` keyed by
`story_id`. Both hit the same index. `Story` has no such relation today, so the
subquery adds nothing to the model. Either way is acceptable; that choice
belongs to PLAN.

### 2.3 Lifecycle rules

- Unmarking a chapter deletes its progress row. Once the last row of a story
  is gone, the story qualifies again (spec §5, A1). Nothing needs adding.
- `story_id` and `chapter_id` cascade on delete, so deleting a story or
  chapter removes its marks. This is unchanged.
- **Any** progress row for the story excludes it, including one on a chapter
  that has since been unpublished. The spec's rule is "≥1 chapter marked as
  read", with no condition on publication. Restricting the rule to published
  chapters would cost a join to `story_chapters` and change nothing for real
  readers. See §7 #1.

## 3. PHP architecture

### 3.1 Public API

Unchanged.

### 3.2 Services

The exclusion is **unconditional**: no setting and no flag (A2). It goes
inside `StoryRepository::getRandomStories()` and needs no new parameter, since
`$viewerId` is already passed in. `StoryService::getRandomStories()` keeps its
signature. Its docblock gains "excludes stories the viewer has started".

The query keeps `inRandomOrder()->limit($nbStories)`. With fewer matches the
query returns fewer rows. The Blade already renders the placeholder last
whatever the count, so spec §4.1 points 5 and 6 need no code.

### 3.3 Policy / authorization

No new rule. The viewer id comes from the authenticated user in
`RandomStoriesComponent`, as it does today.

### 3.4 Events and listeners

None. `MarkChapterReadOnRootCommentPosted` already writes the same progress
rows as the « lu » button, so the « root comment » path in the spec's
vocabulary is covered with no extra work.

### 3.5 Routes, controllers, form requests

None.

## 4. Frontend architecture

None. `random-stories.blade.php` and the scroller are unchanged. No new strings.

## 5. Deptrac

No new edge. Everything stays inside `Story/Private`.

## 6. Testing strategy

Integration tests extend
`Story/Tests/Feature/Stories/Components/RandomStoriesComponentTest.php`, which
renders the component as an authenticated user:

- a story with one chapter marked read by the viewer is excluded;
- a story marked read by **another** user is still shown;
- a story in the viewer's pile à lire, with no chapter read, is still shown
  (decision #1). The story is added to the read list through ReadList's public
  API, or the test asserts only on the progress rows if that pulls ReadList
  into a Story test (PLAN decides);
- after unmarking every chapter, the story is shown again;
- when every eligible story is read, only the placeholder renders (decision #2).

No unit, vitest or visual test is needed: there is no layout change. VERIFY
can be a single dashboard screenshot for a reader who has read some stories.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Which marks count as "read"? | (a) any progress row for the story · (b) only rows on currently published chapters | (a) | Matches the spec's wording; hits the `(user_id, story_id)` index with no join to chapters; (b) only differs for a reader whose sole read chapter was later unpublished |
| 2 | Where the filter lives | (a) always applied inside the repository query · (b) new `excludeRead` flag threaded through service and repository | (a) | Single caller, no opt-out (A2); a flag would be speculative |

Neither was put to the user: both follow directly from the spec and
assumptions A1/A2. They are recorded here so they are not reopened.

## 8. File layout

No new classes.

## 9. Risks acknowledged

- **Heavy readers may see a thin or empty carousel** once they have started
  most of the eligible stories. This is accepted (decision #2, no top-up).
  Revisit if users report an empty « Histoires à découvrir » block.
- `inRandomOrder()` stays a full scan of the eligible set. The new filter
  shrinks that set, so it does not make the scan worse.
