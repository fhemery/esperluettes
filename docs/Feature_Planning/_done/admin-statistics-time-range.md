# Admin statistics — time-range selector

**Status:** DONE — 2026-11-02 · **Domain(s):** `Statistics`

## What it does

A second page-level switch on `/admin/statistics`, beside *Cumulé | Par semaine*: **12 derniers mois** (default) | **Depuis le début**. It applies to every chart on every tab, hidden ones included, in both graph modes, and redraws in place with no reload. It also bounds the weekly bars, which used to span all history. Number tiles never change.

## Key behaviour

- Admin and tech-admin only (existing route); no new role rules.
- Both ranges are embedded in the page (no endpoint, no reload). Existing payload keys = *Depuis le début*; new `recent*` keys = last 12 months (`now()->subMonths(12)->startOfDay()`, UTC).
- Cumulé, 12 months: the line starts at the real level on the start day (seeded from the last daily row strictly before `$from`: its `cumulative_value`, else the sum of earlier deltas), 48 points over the 12 months.
- Par semaine, 12 months: weeks overlapping the range (the start day's week through the current week); current partial week lighter.
- Comment breakdown follows the same range in both modes.
- A metric silent in the range but with earlier history draws a flat line / zero bars, not the empty state. Empty state still tests the full series.
- Choice kept in `localStorage` `statistics.admin.time-range`; unknown value falls back to `recent`. Mode and range are independent.
- Payload roughly doubles (6 charts x 4 datasets); acceptable for an admin page.

## Where the code lives

| Concern | Path |
|---------|------|
| Service | `app/Domains/Statistics/Private/Services/StatisticQueryService.php` (`getChartTimeSeries` seeding, `getWeeklyTimeSeries` `$from`) |
| Components | `stat-widget`, `line-chart`, `multi-line-chart`, `comment-breakdown-chart` Blade components (`recent*` props/keys) |
| View | `index.blade.php` (second `x-shared::segmented-control`, `name="statistics-time-range"`); strings in `statistics::admin` (`time_range_*`) |
| JS | `Private/Resources/js/charts.js` (`currentRange()`, `rangedPayload()`, `renderedMode`/`renderedRange`) |
| Tests | `Statistics/Tests/Feature` (seeding, weekly `$from`, `WeeklyChartPayloadTest`, controller), vitest `charts.test.js`, e2e `e2e/tests/core/admin-statistics-time-range.spec.ts` |
| E2e data | `E2eStatisticsSeeder` (+5000 `global.total_words` twenty months ago), `STATISTICS.oldWords` in `e2e/support/fixtures.ts`, `AdminStatisticsPage.rangeSwitch/selectRange` |

## Extension points used

`Shared` `segmented-control` (WAI-ARIA radiogroup) reused as is. No registry, route, event, table or deptrac edge.

## Decisions worth remembering

- Embedded server-computed variants, not client trimming (would leave ~10 of 48 points) nor a reload.
- Weekly bound = overlapping weeks, not fully-inside weeks.
- 12 months = start-of-day 12 months back, not calendar months.
- Plan vs code: the plan said "no durable e2e spec" and left the seeder alone; after VERIFY the seeder gained the old point and the spec was added and **promoted to core**, because the range logic is browser-only (charts.js + embedded payloads) and no PHP test covers the redraw or persistence. Only `words` has old data, so the core `admin-statistics` assertions are untouched.

## Not done

- Non-goals: other ranges (3 months, year, custom), per-graph ranges, server-side remembering, changing tiles, user-profile statistics, changing the 48-point resolution.
- Retro filed as loop-improvements item 13. No new backlog rows.
