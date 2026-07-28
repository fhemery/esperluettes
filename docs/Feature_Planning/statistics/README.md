# Statistics

**Status:** DONE except the profile surface — 2026-07-27 · **Domain(s):**
`Statistics`

Read this before adding a metric or touching the admin statistics page.

## What it does

Precomputed aggregate metrics, updated by domain events rather than counted on
read. A statistic is a class implementing `StatisticDefinition`, registered in
`StatisticsServiceProvider`, which declares the events it reacts to and how to
recompute itself from scratch. Values land in two tables — a current-value
snapshot and an optional time series — and are rendered by Blade components on
the admin page.

## Key behaviour

- **Event-driven, not queried.** `UpdateStatisticsOnEvent` subscribes to
  `UserRegistered`, `UserDeleted`, `StoryCreated`, `StoryDeleted`,
  `ChapterCreated`, `ChapterDeleted`, `ChapterUpdated`, `CommentPosted` and
  dispatches to whichever definitions declared an interest.
- **Every statistic can recompute itself from scratch** — `statistics:compute
  {key} {--scope-id=}`. There is no separate backfill command; recompute *is*
  the backfill.
- **Scoped by `(statistic_key, scope_type, scope_id)`**, unique. `scope_type` is
  `global` or `user`; `scope_id` is null for global.
- **Time series are lazy** — created on first computation, not eagerly for every
  user.
- **Nulls are returned, not defaulted.** Components decide how to show missing
  data.
- **No public API, deliberately.** Other domains do not read statistics; they
  emit events and Statistics listens.
- **Chart.js is bundled through Vite**, never a CDN.

## Where the code lives

| Concern | Path |
|---------|------|
| Contract | `app/Domains/Statistics/Public/Contracts/StatisticDefinition.php` |
| Registry / compute / query | `Private/Services/Statistic{Registry,ComputeService,QueryService}.php` |
| Event fan-out | `Private/Listeners/UpdateStatisticsOnEvent.php` |
| Definitions | `Private/Definitions/{Global,User}/` |
| Command | `Private/Console/ComputeStatisticCommand.php` (`statistics:compute`) |
| Admin page | `Private/Controllers/Admin/StatisticsController.php`, `/admin/statistics` |
| Components | `x-statistics::{digit,stat-card,stat-widget,stat-summary,line-chart,multi-line-chart,comment-summary,comment-breakdown-chart}` |
| Tables | `statistic_snapshots`, `statistic_time_series` |

## Statistics that exist

Global: total users, stories, chapters, words, comments, root comments.
User-scoped: total stories, chapters, words.

## Not done

- **The whole per-user surface.** The three user-scoped definitions are computed
  and **displayed nowhere** — no `x-statistics::` component is rendered outside
  the admin page. `Private/Resources/lang/fr/profile.php` holds five translation
  keys nothing uses, left behind by the abandoned Phase 5.
  → [`../statistics-profile/`](../statistics-profile/)
- **The three user comment definitions** the plan called for
  (`UserRootCommentsWritten`, `UserTotalCommentsWritten`,
  `UserRootCommentsReceived`) were never written. Same task.
- **Open risks, acknowledged not resolved:** `DECIMAL(20,4)` may be overkill for
  counts (`BIGINT` would do); and there is no answer for backfilling across a
  changed event payload — recompute assumes today's event shape.
- Deliberate non-goals: CSV/JSON export, comparison to averages, user-defined
  goals, milestone webhooks.

The pre-loop planning document is in git history:
`git show f1d50704:docs/Feature_Planning/Statistics.md`.
