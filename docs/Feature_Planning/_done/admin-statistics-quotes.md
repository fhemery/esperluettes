# Admin statistics — quotes section

**Status:** DONE — 2026-10-24 · **Domain(s):** `Statistics`, `Quote`

## What it does

`/admin/statistics` gains a sixth summary tile « Citations » and a fourth tab
« Citations » holding one `stat-widget` for the new global statistic
`global.total_quotes`: the **net** number of quotes that currently exist, with
its daily series (cumulative curve, or weekly net bars via the existing
« Cumulé / Par semaine » switch). To make disappearances observable, Quote now
emits three new domain events and registers all four of its events with the
bus. History is rebuilt from the `quotes` table, not by replaying events.

## Key behaviour

- Visibility: unchanged page rule, `admin` and `tech-admin` only (not
  moderators, DECISIONS #6). Aggregate count only; events carry ids, never text.
- Deltas: save +1 · reader deletes own quote −1 (hard delete, gone for good) ·
  user deactivated −(visible quotes) · reactivated +(restored rows) · user
  deleted −(visible quotes; rows already hidden by a deactivation are not
  subtracted twice) · chapter/story deleted 0 (rows remain, still counted).
- Quote takes the count **inside its own listeners, before** changing rows, and
  emits `UserQuotesRemoved` / `UserQuotesRestored` **only if count > 0**.
  Statistics never listens to Auth and never reads the `quotes` table.
- Restore count is `onlyTrashed()->count()` taken before `restore()`, not
  `restore()`'s return value (differs SQLite vs MySQL).
- `recompute()` (`statistics:compute global.total_quotes`) clears the key, then
  applies `QuotePublicApi::getDailyNetChanges()` (`+1` on `DATE(created_at)` of
  every row incl. trashed, `−1` on `DATE(deleted_at)` of trashed rows, zero-net
  days dropped, ascending). `eventsProcessed` = number of days applied.
- Counter-intuitive: after a recompute, hard-deleted quotes (reader-deleted, or
  of deleted users) vanish from history entirely — both their creation and
  their removal. The snapshot stays right; the curve differs from the live one
  for those days. Accepted (architecture §9 risk 1).
- No clamp: the snapshot can go negative if live deltas run on a key that was
  never backfilled.

## Deployment note

Run `./vendor/bin/sail artisan statistics:compute global.total_quotes` once
after deploy. Until then the key holds only post-deploy deltas, and deleting a
pre-existing quote drives it below the real total (possibly negative).

## Where the code lives

| Concern | Path |
|---------|------|
| Quote events (new) | `app/Domains/Quote/Public/Events/{ChapterPassageQuoteDeleted,UserQuotesRemoved,UserQuotesRestored}.php` |
| Event registration (all four incl. `ChapterPassageQuoted`) | `app/Domains/Quote/Public/Providers/QuoteServiceProvider.php` |
| Emission | `Quote/Private/Services/QuoteService.php` (`delete()`), `Quote/Private/Listeners/{SoftDeleteOnUserDeactivated,RestoreOnUserReactivated,DeleteQuotesOnUserDeleted}.php` |
| History read | `QuotePublicApi::getDailyNetChanges()` → `QuoteService::getDailyNetChanges()` |
| Statistic | `app/Domains/Statistics/Private/Definitions/Global/TotalQuotesStatistic.php` |
| Registration + event subscriptions | `app/Domains/Statistics/Public/Providers/StatisticsServiceProvider.php` |
| View | `Statistics/Private/Resources/views/pages/admin/index.blade.php` (grid `lg:grid-cols-6`, tab `quotes`, `<x-shared::tabs scrollable>`) |
| Lang | `Statistics/Private/Resources/lang/fr/admin.php` (`quotes`, `tab_quotes`) |
| E2E seed | `Statistics/Database/Seeders/E2eStatisticsSeeder.php` + `e2e/support/fixtures.ts` (`quotes: [2, 1, 0, -1, 3, 1]`) |
| Tests | `Quote/Tests/Feature/{QuoteEventsTest,QuotePublicApiDailyNetChangesTest}.php`, `Statistics/Tests/Feature/TotalQuotesStatisticTest.php`, `Statistics/Tests/Feature/Admin/StatisticsControllerTest.php`, `e2e/tests/core/admin-statistics.spec.ts` |
| Deptrac | `StatisticsPublic`, `StatisticsPrivate`, `StatisticsTests` → `QuotePublic` |

No migration, no route, no controller change.

## Extension points used

- **Statistics** `StatisticDefinition` registry: `global.total_quotes`, listening
  to the four `Quote.*` event names; the event classes added to
  `registerEventListeners()` (without it, the statistic silently never updates).
- **Events** bus: `registerEvent()` for the four Quote events, so stored copies
  rehydrate and get audit-log summaries.

## Decisions worth remembering

- Quote emits count-carrying events rather than Statistics listening to Auth
  (B raced with Quote's listeners and could not count a hard-deleted user's
  rows). `UserQuotesRemoved` covers both deactivation and deletion.
- Recompute from the table, not stored events: past deletions were never
  recorded and `events_domain` may be pruned after 90 days. The only statistic
  deviating from the replay recipe.
- Builds on `quote-hard-delete`: `deleted_at` set ⇔ owner currently
  deactivated, so reactivation restores exactly what deactivation hid.
- Registering `ChapterPassageQuoted` did not double the author notification:
  `NotifyAuthorsOnQuoteCreated` subscribes by FQCN and was already wired.

## Code vs plan

- Phase 5 did not plan `scrollable` on `<x-shared::tabs>`; VERIFY found the
  fourth tab clipped at 375px, fixed in `b0cfa03a`.
- `e2e/pages/AdminStatisticsPage.ts::hoverSlot` was reworked during VERIFY
  (`1527ed0e`): wait for bars to span the canvas (a chart in a just-opened tab
  is first drawn narrow), and count back from the last bar by the bar pitch
  when a zero-height slot is missing. Not in the plan.
- Plan test "emits UserQuotesRestored with the number of restored rows" became
  "emits no UserQuotesRestored when nothing is restored"; the restored-count
  case is covered by "…without counting a quote the reader deleted".
- Otherwise the code matches the plan.

## Not done

- Non-goals: per-user quote statistics (backlog `statistics-profile/`),
  time-range selector (backlog `admin-statistics-time-range/`), breakdowns
  (per story/chapter, with/without note), any change to reader behaviour.
- Cut mid-build: nothing.
- Open: assumptions not asked — quotes on deleted chapters/stories still count;
  access rule is the page's existing one. Both reversible.
- Accepted drift: rebuilt history omits hard-deleted quotes (see above).
- E2E: the task left no spec in `e2e/tests/features/`; it extended the existing
  `e2e/tests/core/admin-statistics.spec.ts` (Citations tab, negative weekly
  tooltip), which stays in core.
- No new backlog rows.
