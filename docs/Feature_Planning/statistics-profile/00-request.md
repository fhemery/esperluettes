# Statistics — per-user statistics on the profile — request

Migrated from `Statistics.md` Phase 5, the only phase of that plan never
delivered. The rest is live — see
[`../statistics/README.md`](../statistics/README.md).

## What I want

Show a user's own statistics on their profile: how much they have written, and
their comment activity.

## Why

The Statistics domain computes per-user values today and displays none of them.
Three user-scoped definitions exist (`UserTotalStories`, `UserTotalChapters`,
`UserTotalWords`) and **no `x-statistics::` component is rendered anywhere
outside the admin page** — the whole per-user half of the domain is dark.

## What already exists, unused

- The three user definitions above, computed and stored.
- `Private/Resources/lang/fr/profile.php` — five translation keys
  (`root_comments_written`, `total_comments_written`, `root_comments_received`,
  `comments_written_over_time`, `comments_received_over_time`) that **nothing
  renders**. Phase 5 was prepared and abandoned.
- `comment-summary` and `comment-breakdown-chart` components, used only on the
  admin page.

## What the original plan asked for

- `UserRootCommentsWrittenStatistic`, `UserTotalCommentsWrittenStatistic`,
  `UserRootCommentsReceivedStatistic` — none of the three exists.
- A profile component displaying them.

## Open questions for REFINE

- **Which statistics** — the three comment ones only, or also the existing
  stories/chapters/words?
- **Which surface** — a Statistics tab registered through `ProfileTabRegistry`,
  or a block inside an existing tab? A new tab is the pattern the registry was
  built for, and the registry work explicitly noted that adding a Statistics tab
  should touch zero Profile files.
- **Who may see another user's statistics** — everyone, confirmed users only,
  or the owner alone? Does it need a setting like the other profile tabs?

## Explicitly out of scope

- The `Statistics.md` future enhancements: export, comparison to averages,
  user-defined goals, webhooks.
