# Admin statistics — cumulative / non-cumulative graph toggle

**Status:** DONE — 2026-09-27 · **Domain(s):** `Statistics` (owner), `Shared`
(generic control) · **Spec:** [functional](./01-functional.md) ·
[architecture](./02-architecture.md) · [plan](./03-plan.md) ·
[decisions](./DECISIONS.md)

## What it does

`/admin/statistics` gets a page-level switch **Cumulé | Par semaine** above the
tabs. *Par semaine* redraws every chart on every tab, hidden tabs included, as
bars: one per calendar week (Monday to Sunday, UTC = app timezone), holding the
week's **net** change. The server computes both datasets and embeds them in the
page, so switching needs no reload and no endpoint. The choice is remembered per
browser in `localStorage`. The default is *Cumulé*, and the number tiles never
change.

## Key behaviour

- Access: unchanged (`role:admin,tech-admin` on the existing route). No new route, table, event or deptrac edge.
- Weekly value = sum of the **daily deltas** (`value`, never `cumulative_value`). It can be negative (deletions, words removed). Bars sum to the cumulative total.
- Series run from the week of the first daily row to the **current week**, with no gaps (empty weeks are 0). The last bar is the partial current week: alpha 0.35 instead of 0.8, and its tooltip ends with "(semaine en cours)".
- The weekly series is **not bounded by the chart date range and not resampled**. The cumulative path goes through `getChartTimeSeries()` (48-point resample); weekly draws every week since the first data point, so bars thin out as history grows (accepted risk, see Not done).
- Comment breakdown: stacked bars. Roots are aligned to the total **by week start**, not by index; a missing root week counts as 0. Replies = total − roots, not clamped.
- Tooltip (weekly): title `Semaine du {d MMM yyyy} : {sum of the stack}`, with values formatted by `toLocaleString(locale)`. The stacked chart adds one `{série} : {n}` line per series; single-series charts add none.
- Mode switch = **destroy + re-create** each chart, because Chart.js 4 cannot change line ↔ bar in place. A chart with no weekly points stays a line in weekly mode. An empty metric keeps its Blade empty state in both modes.
- Initial mode: `charts.js` loads after Alpine and so misses the control's init event. It reads the control's `data-value` at mount instead, and listens to `segmented-control-change` only for later changes. This differs from architecture §4.4, which planned to use the init event; the `data-value` mirror was added in PLAN (open item 2).
- An unreadable, throwing or unknown stored value silently falls back to *Cumulé*. A failed write only means the choice is not remembered.

## Where the code lives

| Concern | Path |
|---------|------|
| Weekly aggregation | `app/Domains/Statistics/Private/Support/WeeklyAggregator.php` |
| Query service | `StatisticQueryService::getWeeklyTimeSeries()` in `Statistics/Private/Services/StatisticQueryService.php` |
| Chart payloads | `Statistics/Private/Resources/views/components/{stat-widget,line-chart,multi-line-chart,comment-breakdown-chart}.blade.php` (`data-weekly-points`, per-series `weeklyPoints`, `weekTooltip`/`currentWeekLabel` in options) |
| Page + switch | `Statistics/Private/Resources/views/pages/admin/index.blade.php` (control `name="statistics-graph-mode"`, `storage-key="statistics.admin.graph-mode"`) |
| Chart JS | `Statistics/Private/Resources/js/charts.js` (module-level chart registry, `renderAll(mode)`, `initWeeklyBarChart`) |
| Shared control | `Shared/Resources/views/components/segmented-control.blade.php`, `Shared/Resources/js/segmented-control.js` (registered in `Shared/Resources/js/app.js`) |
| i18n | `Statistics/Private/Resources/lang/fr/admin.php` (`graph_mode_*`, `current_week`, `week_tooltip`) |
| E2E data | `Statistics/Database/Seeders/E2eStatisticsSeeder.php` (wired into `DatabaseSeeder`'s e2e list), mirrored by `STATISTICS` in `e2e/support/fixtures.ts`; page object `e2e/pages/AdminStatisticsPage.ts` |
| Tests (PHP) | `Statistics/Tests/Unit/WeeklyAggregatorTest.php`, `Tests/Feature/WeeklyTimeSeriesTest.php`, `Tests/Feature/WeeklyChartPayloadTest.php`, `Tests/Feature/Admin/StatisticsControllerTest.php`; `Shared/Tests/Feature/View/Components/SegmentedControlTest.php` |
| Tests (JS) | `Statistics/Private/Resources/js/charts.test.js`, `Shared/Resources/js/segmented-control.test.js` |
| Migrations | none |

## Extension points used

- New Shared component `x-shared::segmented-control`: a generic WAI-ARIA radiogroup. It dispatches `segmented-control-change` `{name, value}` on `window` on init and on every change, mirrors its value in `data-value` on the root, and can persist via `storageKey`. It is meant to be reused by the time-range selector. No registry plugged into.

## Decisions worth remembering

- Aggregation is server-side, in PHP, over daily rows, not in SQL (no MySQL/SQLite dialect split) and not in JS (the week/timezone logic stays under feature tests). DECISIONS #8.
- Both datasets are embedded up front and switched in place. Revisit this if the range selector makes the page server-driven (architecture §7.1).
- Weeks are in UTC, the app timezone, because daily buckets are UTC days. A Sunday-night event in Paris lands in the next week.
- `segmented-control.js` must use `this.$root`, not `this.$el`: from a button handler, Alpine 3.15 resolves `$el` to that button. This was a Phase 2 bug fixed in Phase 4, and the unit test pins it.
- Carbon: always `startOfWeek(Carbon::MONDAY)`. Tests run with locale `zz`, which would shift the week start otherwise.

### Assumptions made without asking (DECISIONS.md) — the user may want to reverse these

1. Labels "Cumulé" / "Par semaine"; tooltip "Semaine du {date} : {valeur}". *(confirmed on replay)*
2. The week starts Monday, in the app timezone. *(confirmed on replay)*
3. Number tiles are unaffected. *(confirmed on replay)*
4. Charts on hidden tabs switch too. *(confirmed on replay)*
5. The switch is a keyboard-operable button group with a selected state. *(confirmed on replay)*
6. No specific mobile work. *(confirmed on replay)*
7. The `$el` → `$root` fix above (BUILD 4/4).
8. **Not confirmed:** the weekly tooltip title shows the **sum of the stack**; per-series lines appear only on the stacked chart. The spec said "per series on the stacked chart", without specifying the title (BUILD 4/4).

## Not done

- **Deliberate non-goals** (spec §8): other granularities (day, month, admin-chosen), per-graph toggles, remembering the mode server-side or across devices, changing the tiles, statistics outside the admin page (the separate `statistics-profile/` task).
- **Time-range selector** (*12 derniers mois* / *Depuis le début*): planned follow-up. It also addresses the unbounded weekly bars. Pushed to the backlog as [`admin-statistics-time-range/`](../admin-statistics-time-range/00-request.md).
- Nothing was cut mid-build. All 4 phases are DONE, and the VERIFY checklist is fully green (screenshots in [`shots/`](./shots/)).
- Code/plan drift: architecture §4.4 (initial mode from the init event) was replaced by the `data-value` read, as explained above. Architecture §8's file layout omits `WeeklyChartPayloadTest.php`, the E2E seeder and the page object, which all exist.
- **E2E**: `e2e/tests/features/admin-statistics-graph-toggle.spec.ts` was **deleted** at WRAP, because it guards one admin page and nothing app-wide. Payload and access are covered by PHP tests; chart drawing and switching by `charts.test.js`. The seeder, the `STATISTICS` fixture and `AdminStatisticsPage.ts` were kept for the time-range follow-up.
- Open questions: none.
