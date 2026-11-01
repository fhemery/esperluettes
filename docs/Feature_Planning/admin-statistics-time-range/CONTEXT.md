# Admin statistics — time-range selector — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → BUILD (2026-11-01)

- Zero blocking questions asked: the request fixes the options and default; everything else follows the shipped graph-toggle precedent (`_done/admin-statistics-graph-toggle.md`). Fast path: §2 and §3 written here.
- Trap: `getChartTimeSeries()` with a `$from` starts `lastCumulative` at 0 and only reads daily rows `>= from`. A metric with no change in the last 12 months would draw a flat 0 line. Phase 1 must seed the starting level from the last daily row before `$from` (test-first).
- Likeliest to be reversed: the 12-month default (A1) and remembering the choice in `localStorage` (A2).
- Both ranges are embedded in the page (no reload, no endpoint), as the graph-toggle design did; the "server-driven" revisit the request mentions is not needed.

### BUILD phase 1 → phase 2 (2026-11-01)

- Payload keys as planned: line chart `data-recent-points` / `data-recent-weekly-points`, series `recentPoints` / `recentWeeklyPoints`, options `recentRangeMin` / `recentRangeMax`. `line-chart` takes new props `recent` / `recentWeekly`; `multi-line-chart` series items take `recentData` / `recentWeeklyData`.
- Seeding uses the last row's `cumulative_value`, else the sum of all earlier deltas (the delta-only fallback would have under-seeded). Recent range spans 48 points over 12 months, starting at the seeded level.
- Recent options are emitted even for the empty state's sibling case; empty state still tests the full series. `app/Domains/Statistics/AGENTS.md` invariant on weekly range updated.

### BUILD phase 2 → VERIFY (2026-11-01)

- `charts.js`: `rangedPayload()` swaps in the `recent*` sets and `recentRangeMin/Max` when range is `recent`; a payload without `recentPoints` falls back to the full set. Mode and range tracked separately (`renderedMode`, `renderedRange`). No seeder change made; VERIFY should check whether `E2eStatisticsSeeder` has a point older than 12 months.

### VERIFY → WRAP (2026-11-01)

- PASS, all six checklist rows (§5). No spec added, no code changed; nothing to promote or delete in `e2e/tests/features/`.
- Fold in: the e2e seeder has no data older than 12 months, so the two ranges are only distinguishable with hand-added old rows. Manual eyeball: mobile layout puts the two switches on separate rows (`shots/mobile.png`).

## 1. Functional spec

### 1.1 Overview

A second page-level switch on `/admin/statistics`, next to *Cumulé | Par semaine*: **12 derniers mois | Depuis le début**. It applies to every chart on every tab (hidden ones included), in both graph modes. It bounds the weekly bars, which until now spanned all history.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Plage | The period the charts show. |
| *12 derniers mois* | From the day 12 months before today (start of day, UTC) to today. Default. |
| *Depuis le début* | From the first data point to today (today's behaviour). |

### 1.3 Roles & visibility

Unchanged: admin and tech-admin only (existing route). Nobody else sees the page.

### 1.4 Functional requirements

1. The switch sits beside the graph-mode switch (same row, wrapping on narrow screens); label (accessible name) *Période des graphiques*.
2. Default is *12 derniers mois*. Changing it redraws all charts in place, no reload; the choice is remembered per browser, like the graph mode.
3. *Cumulé*, 12 months: the line starts at the **real cumulative level** on the start day (not at 0), over the same 48-point resampling, now spread across the 12 months (finer than the all-history view).
4. *Par semaine*, 12 months: only the weeks overlapping the range (the week containing the start day through the current week); bars still sum the week's net change; the current partial week stays lighter.
5. Comment breakdown follows the same range in both modes (roots aligned to the total by week, as before).
6. A metric with no point in the range but earlier history shows a flat line at its level (cumulative) or zero bars (weekly), not the empty state. A metric with no data at all keeps the empty state.
7. Number tiles never change.
8. Unknown or unreadable stored value falls back to *12 derniers mois*.

### 1.5 Lifecycle

N/A — pure display over existing aggregates; no data is created or deleted.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Admin pages only; no `user` / `user-confirmed` distinction. |
| Visibility / privacy | N/A — aggregates, admin-only. |
| Settings | None; browser `localStorage` only (A2). |
| Notifications / Domain events | N/A. |
| Statistics | This is the Statistics domain's admin page; no new metric. |
| Moderation, Media, Search | N/A. |
| Lifecycle / cascade | N/A. |
| i18n | New French strings in `statistics::admin`. |
| Mobile | Switches wrap (`flex-wrap`); no specific work. |
| Accessibility | Reuses the WAI-ARIA `segmented-control` radiogroup, with its own label. |

### 1.7 Out of scope

Other ranges (3 months, year, custom dates), per-graph ranges, server-side remembering, changing the number tiles, the user-profile statistics, changing the 48-point resolution.

### 1.8 Open questions

None blocking.

## 2. Architecture

### 2.1 Domain placement

`Statistics` only; `Shared` `segmented-control` reused as is. No table, route, event or deptrac edge.

### 2.2 Data model

None.

### 2.3 PHP architecture

- `StatisticQueryService::getWeeklyTimeSeries(key, scopeType, scopeId, ?DateTimeInterface $from = null)`: aggregate as today, then keep weeks whose Monday `>= $from->startOfWeek(MONDAY)`. Aggregation is not changed (the weekly sums are unaffected by dropping early weeks).
- `StatisticQueryService::getChartTimeSeries()`: when `$from` is given, prepend to the daily points the last daily row strictly before `$from` (if any), so the resampler's running cumulative starts at the right level. Cumulative rows carry `cumulative_value`; fall back is the resampler's existing delta path. Behaviour with `$from = null` is unchanged.
- The recent bound is computed once per component render: `now()->subMonths(12)->startOfDay()`.
- Components (`stat-widget`, `comment-breakdown-chart`) compute both variants (full = today's, recent = with `from`) and pass them down. Payload keys added: line chart `data-recent-points`, `data-recent-weekly-points`; per series `recentPoints`, `recentWeeklyPoints`; options `recentRangeMin` / `recentRangeMax`. Existing keys keep their meaning (= *Depuis le début*), so existing tests stay valid.
- The empty state still tests the **full** series.

### 2.4 Frontend

- `index.blade.php`: second `x-shared::segmented-control`, `name="statistics-time-range"`, options `recent` / `all`, `selected="recent"`, `storage-key="statistics.admin.time-range"`; both controls in one `flex flex-wrap gap-4` row.
- `charts.js`: `currentRange()` reading the control's `data-value` (`'all'` only if exactly that, else `'recent'`); `createChart(canvas, kind, payload, mode, range)` picks `points`/`recentPoints` and the weekly counterparts, and `rangeMin/Max`; the registry re-renders on `segmented-control-change` for either control name (mode and range tracked separately; re-render only if one changed). Same pattern as `currentMode()` (read `data-value` at mount; listen for later changes).
- A range with an empty recent weekly array falls back like today's "no weekly points → stays a line".

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

PHP feature: `getChartTimeSeries` seeding (metric silent in range keeps its level), `getWeeklyTimeSeries` `$from` filter (boundary week kept), page payload carries recent keys and the second control. Vitest (`charts.test.js`): `currentRange()`, range switch re-creates charts with the right point sets and axis bounds, mode and range independent. VERIFY: browser check with `E2eStatisticsSeeder` data (extend it with a point older than 12 months if it has none) via `AdminStatisticsPage.ts`; no durable e2e spec (guards one admin page, as before).

### 2.7 Tradeoffs locked

| # | Question | Options | Chosen | Why / what would flip it |
|---|----------|---------|--------|--------------------------|
| 1 | Where is the range applied | client trim of existing data; server query-string reload; server-computed variants embedded | embedded variants | Client trim would leave ~10 points of 48 on 12 months; reload loses the in-place switching. Flip if the payload gets too heavy (4 datasets per chart). |
| 2 | Weekly bound | weeks fully inside; weeks overlapping | overlapping | The start day's week is not cut off mid-way. |

### 2.8 Risks acknowledged

Page payload roughly doubles (about 1 to 2 hundred points per chart, 6 charts): acceptable for an admin page; revisit if it ever needs more ranges.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Server: range-aware series + payloads | S | — | DONE |
| 2 | Client: range switch, charts, i18n | S | 1 | DONE |

Working agreement: one phase = one commit; failing test first.

### Phase 1 — Server: range-aware series + payloads

**Goal.** Components emit full and 12-month variants with correct starting level. **Reads.** §2.3.

**Deliverables.** `StatisticQueryService.php` (seeding, weekly `$from`); `stat-widget`, `line-chart`, `comment-breakdown-chart`, `multi-line-chart` blade components.

**Tests.** In `Statistics/Tests/Feature`: `getChartTimeSeries` seeds from the last row before `from`; `getWeeklyTimeSeries` honours `$from` (boundary week kept, earlier dropped); `WeeklyChartPayloadTest` extended for recent keys (single and stacked, week alignment kept).

**Acceptance.**
- ✅ Existing payload keys unchanged; new keys present.
- ✅ `pnpm run gate` green.

### Phase 2 — Client: range switch, charts, i18n

**Goal.** The second switch redraws every chart in place and persists. **Reads.** §2.4. **Leaves behind from 1:** the payload keys listed in §2.3.

**Deliverables.** `index.blade.php`, `charts.js`, `charts.test.js`, `lang/fr/admin.php` (`time_range_label` *Période des graphiques*, `time_range_recent` *12 derniers mois*, `time_range_all` *Depuis le début*), seeder data if needed.

**Tests.** Vitest as in §2.6; a controller test that the page renders both controls.

**Acceptance.**
- ✅ Switching range redraws all four chart kinds in both modes; mode switch keeps the range and vice versa.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| `/admin/statistics` | Two switches on one row, wrap on mobile; default *12 derniers mois* | |
| Cumulé, 12 mois | Line starts at real level, not 0 | |
| Par semaine, 12 mois | About 53 bars max; current week lighter | |
| Depuis le début | Identical to previous behaviour | |
| Hidden tabs | Switch applies after changing tab | |
| Reload | Choice remembered; bad stored value falls back to 12 mois | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-11-01 | REFINE | Options and default (from request) | *12 derniers mois* default, *Depuis le début*; applies to both modes | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | "12 derniers mois" = start of day, 12 months before today (UTC), not calendar months. | REFINE | Yes |
| A2 | Choice persists in `localStorage` like the graph mode. | REFINE | Yes |
| A3 | Weekly view keeps weeks overlapping the range. | REFINE | Yes |
| A4 | Labels: *12 derniers mois* / *Depuis le début*, group label *Période des graphiques*. | REFINE | Yes |
| A5 | Silent-in-range metrics show a flat line, not the empty state. | REFINE | Yes |

## 5. Build & verify log

- Phase 1 (2026-11-01): DONE. Server seeding, weekly `$from`, recent payload variants; new Statistics feature tests green.
- Phase 2 (2026-11-01): DONE. Second segmented control, range-aware `charts.js`, French strings; vitest and controller test added.

### VERIFY (2026-11-01) — PASS

Driven on the e2e instance (admin@e2e.test) with a throwaway flow; every screenshot opened (`shots/`). `E2eStatisticsSeeder` has only 6 weeks of data (nothing older than 12 months), so for the check I temporarily wrote old rows into `database/e2e.sqlite` (words +5000 twenty months ago; quotes all shifted 15 months back). Seeder left untouched, no spec added (checklist says no durable e2e spec); the existing `core/admin-statistics` spec still passes.

| Surface | Check | OK? |
|---------|-------|-----|
| `/admin/statistics` | Two switches on one row, wrap on mobile; default *12 derniers mois* | ✅ default recent + cumulative; at 375px the switches stack on two rows (`mobile.png`), no horizontal overflow |
| Cumulé, 12 mois | Line starts at real level, not 0 | ✅ words starts at 5000 (`words-cum-12m.png`); quotes silent for 15 months draws a flat line at 6, not 0 (`quotes-cum-12m.png`) |
| Par semaine, 12 mois | About 53 bars max; current week lighter | ✅ payload 53 weeks vs 88 / 71 for all history; early 5000 bar gone (`words-week-12m.png`), last bar lighter; silent quotes shows empty bars, not empty state |
| Depuis le début | Identical to previous behaviour | ✅ old 5000 bar back from Feb 2025 (`words-week-all.png`) |
| Hidden tabs | Switch applies after changing tab | ✅ content/quotes tabs opened after the switch show the chosen range |
| Reload | Choice remembered; bad stored value falls back to 12 mois | ✅ weekly+all survives reload; `garbage` falls back to recent |

Note: the e2e seeder cannot tell the ranges apart (no point older than 12 months); adding one would shift the tile and weekly fixtures the core spec asserts, so deliberately not done.
