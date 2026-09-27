# Admin statistics — cumulative / non-cumulative graph toggle — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Weekly aggregation service (`WeeklyAggregator` + `getWeeklyTimeSeries()`) | S | — | DONE |
| 2 | Shared `x-shared::segmented-control` component (shared infrastructure) | S | — | DONE |
| 3 | Chart components ship the weekly payload + weekly i18n strings | S | 1 | TODO |
| 4 | `charts.js` weekly mode, chart registry and the switch on the page | M | 2, 3 | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 1 and 2 are independent of each other. There is no security phase: the
page, its route and its `role:admin,tech-admin` guard are unchanged, and no
endpoint is added (architecture §3.3, §3.5). The existing access tests in
`app/Domains/Statistics/Tests/Feature/Admin/StatisticsControllerTest.php` must
stay green in every phase.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Run the gate with its output redirected:
  `pnpm run gate > /tmp/gate.log 2>&1 && echo GATE_GREEN || tail -40 /tmp/gate.log`.

### Traps every phase should know

- **Pest global helpers collide.** Pest loads every test file into one process.
  `ChartTimeSeriesTest.php` already declares a global `seedDailyPoint()` and
  `TimeSeriesResamplerTest.php` a global `makePoint()`. A new test file must not
  declare a function with either name (fatal "Cannot redeclare"). Use distinct
  names (`seedWeeklyDaily()`, `dailyPoint()`) or closures.
- **Tests run with `APP_LOCALE=zz`** (`.env.testing`): `__('statistics::admin.x')`
  returns the raw key, so assertions on translated strings compare keys.
  `Shared/Tests/Unit/TranslationKeysExistTest.php` then checks every static key
  exists in a `fr` lang file — add the key to the lang file in the same phase
  that first uses it.
- **Carbon 3's `startOfWeek()` follows the locale** (and the test locale is
  `zz`). Always pass the day explicitly: `startOfWeek(Carbon::MONDAY)`.

---

## Phase 1 — Weekly aggregation service

**Goal.** `StatisticQueryService::getWeeklyTimeSeries()` returns one net-delta
point per calendar week (Monday → Sunday, UTC) from the first daily row's week to
the current week, computed in PHP by a pure `WeeklyAggregator`.

Reads: architecture §2.1, §3.2, §7 rows 3 and 5. Starts from the current code:
`StatisticQueryService` has `getTimeSeries()` and `getChartTimeSeries()`;
`statistic_time_series` stores sparse daily deltas in `value`
(`granularity = 'daily'`, `period_start` cast to `date`); app timezone is `UTC`.

**Deliverables.**
- `app/Domains/Statistics/Private/Support/WeeklyAggregator.php` (new) — pure,
  no DB access:
  ```php
  /**
   * @param TimeSeriesPoint[] $dailyPoints  sparse daily deltas, any order
   * @return TimeSeriesPoint[]              one point per week, oldest first
   */
  public function aggregate(array $dailyPoints, DateTimeInterface $now): array;
  ```
  - `[]` in → `[]` out.
  - First week = `startOfWeek(Carbon::MONDAY)` of the earliest daily point;
    last week = Monday of the week containing `$now`. Every week in between is
    emitted, with `value = 0` when it has no daily row.
  - Each point: `periodStart` = the Monday (Carbon, start of day),
    `granularity = 'weekly'`, `value` = sum of the week's daily `value`
    (negatives kept, not clamped), `cumulativeValue = null`.
  - Daily points dated after `$now`'s week are ignored (defensive; should not
    happen).
- `app/Domains/Statistics/Private/Services/StatisticQueryService.php` (modify) —
  add, next to `getChartTimeSeries()`:
  ```php
  /** @return TimeSeriesPoint[] one point per calendar week, oldest first */
  public function getWeeklyTimeSeries(
      string $statisticKey,
      string $scopeType = 'global',
      mixed $scopeId = null,
  ): array
  ```
  It loads the daily rows via the existing `getTimeSeries($key, $scopeType,
  $scopeId, 'daily')` and returns
  `app(WeeklyAggregator::class)->aggregate($points, now())`. `$scopeId` is typed
  `mixed` to match the sibling methods (architecture §3.2 writes `?int`; `mixed`
  is the existing convention and accepts the same values).
  `getChartTimeSeries()` and `TimeSeriesResampler` are **not** touched.

**Tests.**
- `app/Domains/Statistics/Tests/Unit/WeeklyAggregatorTest.php` (Pest, no
  `TestCase`, like `TimeSeriesResamplerTest.php`; helper named `dailyPoint()`):
  - `it('returns an empty array when there are no daily points')`
  - `it('groups days Monday to Sunday into the week starting on Monday')` —
    Sunday 2026-01-11 and Monday 2026-01-12 land in two different weeks
    (`2026-01-05`, `2026-01-12`).
  - `it('emits a zero point for weeks without any daily row')`
  - `it('keeps negative weekly nets')` — +3 and −5 in one week → −2.
  - `it('includes the current partial week as the last point')` — `$now` on a
    Wednesday, last point is that week's Monday.
  - `it('extends to the current week even when the last activity is older')`
  - `it('handles the year boundary')` — days 2025-12-29 … 2026-01-04 form one
    week starting `2025-12-29`; also a 53-week ISO year (2026-12-28 → week of
    2026-12-28, 2027-01-03 same week).
  - `it('sums weekly values to the total of the daily deltas')`
  - `it('marks every point weekly with a null cumulative value')`
- `app/Domains/Statistics/Tests/Feature/WeeklyTimeSeriesTest.php` (Pest,
  `TestCase` + `RefreshDatabase`, `resetStatistics()` and
  `Carbon::setTestNow(...)` in `beforeEach`, reset in `afterEach`; helper named
  `seedWeeklyDaily()`, **not** `seedDailyPoint()`):
  - `it('returns an empty array for a metric without daily rows')`
  - `it('aggregates stored daily rows into Monday-based weeks up to the current week')`
  - `it('ignores rows of another statistic key and of another scope')`
  - `it('ignores non-daily rows')`
  - `it('sums to the metric cumulative total')` — seed daily rows, compare
    Σ weekly `value` with Σ daily `value`.

**Acceptance.**
- ✅ `getWeeklyTimeSeries('global.total_users')` with no rows returns `[]`.
- ✅ Rows on 2026-01-11 (+2) and 2026-01-12 (+1), `now = 2026-01-21`, yield
  exactly three points: `2026-01-05` → 2, `2026-01-12` → 1, `2026-01-19` → 0.
- ✅ A week with +3 / −5 returns −2 (no clamp).
- ✅ `ChartTimeSeriesTest` and `TimeSeriesResamplerTest` pass unchanged.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Shared `x-shared::segmented-control`

**Goal.** A generic, accessible, single-choice button group in Shared that can
remember its value in `localStorage` and announces changes on `window` — the
control the future time-range selector will reuse (DECISIONS #9).

**Shared infrastructure**: nothing statistics-specific may appear in this
phase's files.

Reads: architecture §1.1, §4.1. Starts from the current code: Shared's
`app.js` registers Alpine components through `register*(Alpine)` functions
(see `tooltip.js` / `registerTooltip`), then calls `Alpine.start()`; vitest
runs `app/Domains/**/Resources/js/**/*.test.js` under happy-dom; `tooltip.test.js`
shows how to test an `Alpine.data` factory without Alpine. Visual family to
match: `x-shared::tabs` / `x-shared::toggle`.

**Deliverables.**
- `app/Domains/Shared/Resources/js/segmented-control.js` (new) —
  `export default function registerSegmentedControl(Alpine)` registering
  `Alpine.data('segmentedControl', ({ name, options, selected, storageKey }) => ({ … }))`:
  - `value` — resolved on `init()`: if `storageKey` is set, read
    `localStorage.getItem(storageKey)` inside `try/catch`; use it only if it is
    one of `options`; otherwise `selected`.
  - `select(value)` — ignores unknown values and no-op re-selection; sets
    `value`; writes `localStorage.setItem(storageKey, value)` inside
    `try/catch` when `storageKey` is set; dispatches.
  - `dispatch()` — `window.dispatchEvent(new CustomEvent('segmented-control-change', { detail: { name, value } }))`,
    and also sets `data-value` on the component root (`this.$el`) so late
    listeners can read the current value (see Phase 4: charts.js loads after
    Alpine has started, so it misses the init event).
  - `init()` calls `dispatch()` once with the resolved value.
  - `onKeydown(event)` — ArrowRight/ArrowDown → next option, ArrowLeft/ArrowUp →
    previous (wrapping), Home/End → first/last; selects it, moves focus to that
    button, `preventDefault()`. (WAI-ARIA radio-group pattern.)
- `app/Domains/Shared/Resources/js/app.js` (modify) — import and call
  `registerSegmentedControl(Alpine)` before `Alpine.start()`.
- `app/Domains/Shared/Resources/views/components/segmented-control.blade.php`
  (new). Props: `name` (required), `options` (`value => label`, required),
  `selected` (default: first option key), `label` (used as `aria-label`,
  required), `storageKey` (default `null`). Markup:
  - root: `role="radiogroup"`, `aria-label="{{ $label }}"`,
    `data-segmented-control`, `data-name="{{ $name }}"`,
    `data-value="{{ $selected }}"` (server default; JS overwrites it),
    `x-data="segmentedControl({ name, options: [keys], selected, storageKey })"`
    (values via `@js`).
  - one `<button type="button" role="radio">` per option with
    `:aria-checked`, roving `:tabindex` (`0` on the selected one, `-1`
    otherwise), `@click="select(key)"`, `@keydown="onKeydown($event)"`,
    `data-value="{{ $key }}"`. Server-rendered `aria-checked` / `tabindex`
    reflect `selected` so the markup is correct before Alpine runs.
  - Tailwind: inline-flex pill group with a border, selected button filled
    (`surface-primary text-on-surface` or equivalent existing tokens),
    `focus-visible:ring-2`.
- `app/Domains/Shared/README.md` (modify) — one bullet in the components list
  next to `tabs` / `toggle`: props, the `segmented-control-change` event
  `{ name, value }`, the `data-value` reflection, and the `storageKey`
  fallback behaviour.

**Tests.**
- `app/Domains/Shared/Resources/js/segmented-control.test.js` (vitest, factory
  captured as in `tooltip.test.js`, `this.$el` stubbed with a real element):
  - `it('starts on the selected option without a storage key')`
  - `it('restores a stored value')`
  - `it('falls back to the default when the stored value is unknown')`
  - `it('falls back to the default when localStorage throws')` — spy
    `Storage.prototype.getItem` to throw.
  - `it('writes the new value on select')`
  - `it('does not throw when localStorage.setItem throws')`
  - `it('dispatches segmented-control-change with name and value on init and on select')`
  - `it('reflects the current value in data-value on its root')`
  - `it('moves the selection with arrow keys, wrapping around')`
  - `it('ignores unknown values passed to select')`
- `app/Domains/Shared/Tests/Feature/View/Components/SegmentedControlTest.php`
  (Pest, `TestCase`, `$this->blade(...)` like `TabsA11yTest.php`):
  - `it('renders a radiogroup with its aria-label and one radio per option')`
  - `it('marks the selected option checked and focusable, the others not')`
  - `it('defaults to the first option when no selection is given')`
  - `it('exposes name, default value and storage key to Alpine')`

**Acceptance.**
- ✅ `<x-shared::segmented-control name="demo" label="Demo" :options="['a' => 'A', 'b' => 'B']" selected="b" />`
  renders `role="radiogroup"`, `aria-label="Demo"`, two `role="radio"`
  buttons, `aria-checked="true"` + `tabindex="0"` on `b` only.
- ✅ A corrupt or throwing `localStorage` yields the `selected` value, no error.
- ✅ No file of this phase mentions statistics.
- ✅ `pnpm run gate` green (includes the asset build: `app.js` still bundles).

---

## Phase 3 — Chart components ship the weekly payload

**Goal.** Every admin statistics chart carries, next to today's cumulative
points, a weekly point set and the weekly tooltip strings in its data
attributes, with no visible change yet.

Reads: architecture §4.3, §4.5. Starts from: `StatisticQueryService` now has
`getWeeklyTimeSeries(string $key, string $scopeType = 'global', mixed $scopeId = null): array`
(Phase 1) returning `TimeSeriesPoint[]` with `periodStart` on Mondays,
`granularity = 'weekly'`, net `value`. Current components:
`stat-widget` → `line-chart` (`data-points`, `data-options`);
`comment-breakdown-chart` → `multi-line-chart` (`data-series`, `data-options`).
`stat-card` also uses `line-chart` (no caller today) and must keep rendering
unchanged.

**Deliverables.**
- `app/Domains/Statistics/Private/Resources/lang/fr/admin.php` (modify) — add:
  - `'graph_mode_label' => 'Mode d’affichage des graphiques'` (control
    aria-label)
  - `'graph_mode_cumulative' => 'Cumulé'`
  - `'graph_mode_weekly' => 'Par semaine'`
  - `'current_week' => 'semaine en cours'`
  - `'week_tooltip' => 'Semaine du :date : :value'` (the placeholders are kept
    raw for JS to replace)
  The mode keys are used in Phase 4; add them now so the lang file changes once.
  `TranslationKeysExistTest` only checks keys that are used, so unused keys are
  harmless.
- `app/Domains/Statistics/Private/Resources/views/components/line-chart.blade.php`
  (modify) — new prop `weekly` (default `[]`, `TimeSeriesPoint[]`). Mapped to
  `[['x' => 'Y-m-d', 'value' => float], …]` and emitted as
  `data-weekly-points='@json(...)'` on the chart container. Add to `$options`:
  `'weekTooltip' => __('statistics::admin.week_tooltip')`,
  `'currentWeekLabel' => __('statistics::admin.current_week')`. Empty-state
  rule unchanged (driven by the cumulative points).
- `app/Domains/Statistics/Private/Resources/views/components/multi-line-chart.blade.php`
  (modify) — each series item may carry `weeklyData` (`TimeSeriesPoint[]`,
  default `[]`), emitted as `weeklyPoints` inside each `data-series` entry;
  same two option strings added to `$options`. Empty-state rule unchanged.
- `app/Domains/Statistics/Private/Resources/views/components/stat-widget.blade.php`
  (modify) — call `getWeeklyTimeSeries($statisticKey, $scopeType, $scopeId)`
  and pass it as `:weekly`.
- `app/Domains/Statistics/Private/Resources/views/components/comment-breakdown-chart.blade.php`
  (modify) — `$weeklyTotal = getWeeklyTimeSeries($totalKey, …)`,
  `$weeklyRoot = getWeeklyTimeSeries($rootKey, …)`. Build weekly replies by
  aligning on `periodStart` (Y-m-d), **not** on index — the two metrics can
  start on different weeks: for every week present in `$weeklyTotal`,
  `replies = total − (root for that week ?? 0)`, **not clamped** (DECISIONS #7).
  Roots series gets `weeklyData = $weeklyRoot` padded to the same weeks as the
  total (0 where missing). The cumulative computation with `max(0, …)` stays
  as is.

**Tests.**
- `app/Domains/Statistics/Tests/Feature/WeeklyChartPayloadTest.php` (Pest,
  `TestCase` + `RefreshDatabase`, `resetStatistics()`, fixed
  `Carbon::setTestNow`, seed `statistic_time_series` daily rows directly with a
  helper named `seedPayloadDaily()`; render with `$this->blade(...)` and decode
  the attributes):
  - `it('stat-widget ships weekly points next to the cumulative points')`
  - `it('stat-widget weekly points are Monday-based net deltas')`
  - `it('line-chart options carry the weekly tooltip and current-week strings')`
  - `it('comment-breakdown weekly replies are total minus roots, negatives preserved')`
    — a week with total −1 and roots +1 gives replies −2.
  - `it('comment-breakdown aligns weekly roots and replies by week when roots start later')`
  - `it('renders the empty state when the metric has no data')`
  - `it('line-chart without a weekly prop still renders with an empty weekly payload')`

**Acceptance.**
- ✅ `GET /admin/statistics` as an admin returns 200 and every
  `data-statistics-line-chart` container has a `data-weekly-points` attribute.
- ✅ The comment-breakdown series each carry `weeklyPoints` of equal length.
- ✅ Existing `StatisticsControllerTest` (admin 200, confirmed user redirected
  to dashboard, guest redirected to login) passes unchanged.
- ✅ Charts look exactly as before in the browser (charts.js ignores the new
  attributes).
- ✅ `pnpm run gate` green.

---

## Phase 4 — Weekly rendering in `charts.js` and the switch on the page

**Goal.** `/admin/statistics` shows a *Cumulé | Par semaine* switch above the
tabs; choosing *Par semaine* redraws every chart on every tab as weekly bars
(stacked for the comment breakdown, lighter current week, negative bars below
zero), and the choice survives a reload in the same browser.

Reads: architecture §4.2, §4.4, §7 row 4. Starts from:
- Shared ships `<x-shared::segmented-control name label :options selected storageKey>`
  (Phase 2). It dispatches `window` event `segmented-control-change` with
  `detail: { name, value }` on Alpine init and on every change, and mirrors the
  current value in `data-value` on its root `[data-segmented-control][data-name="<name>"]`.
- Chart containers carry weekly data (Phase 3): `data-statistics-line-chart`
  has `data-weekly-points` (`[{ x: 'Y-m-d', value }]`, oldest first, last =
  current week); `data-statistics-multi-line-chart` series have `weeklyPoints`;
  `data-options` has `weekTooltip` (`'Semaine du :date : :value'`) and
  `currentWeekLabel`.
- Lang keys `statistics::admin.graph_mode_label`, `graph_mode_cumulative`,
  `graph_mode_weekly` exist (Phase 3).

**Ordering fact to design around.** `app.js` (Alpine) loads in the page head
and `charts.js` is `@push('scripts')`ed at the end of the body; both are
module scripts, so Alpine has started — and the control has already
dispatched its init event — before `charts.js` registers any listener. So
`charts.js` reads the **initial** mode from the control's `data-value` at mount
time, and listens to the event for later changes. The listener is also
idempotent, so an init event arriving after mount does no harm.

**Deliverables.**
- `app/Domains/Statistics/Private/Resources/js/charts.js` (modify):
  - Named exports for the pure helpers (keep `window.StatisticsCharts` as is):
    `formatChartData`, `formatMultiChartData` (existing) and new
    `formatWeeklyChartData(points, label, options)`,
    `formatWeeklyMultiChartData(series, options)`, `weeklyTooltipText(pattern,
    dateLabel, value, isCurrentWeek, currentWeekLabel)`.
  - `formatWeeklyChartData` → `{ labels: [Monday strings], datasets: [{ label,
    data: values, backgroundColor: [per-bar colours], borderColor }] }`; the
    last bar's colour is the same hue with reduced alpha (e.g. `rgba(…, 0.35)`
    vs `rgba(…, 0.8)`). Negative values pass through unchanged.
  - `formatWeeklyMultiChartData` → one bar dataset per series, same last-bar
    treatment per series; stacking is set in chart options.
  - `initWeeklyBarChart(canvas, data, options, { stacked })` — `type: 'bar'`,
    category x axis labelled with the Monday formatted `d MMM yyyy`
    (date-fns, locale from `options.locale`), `y.beginAtZero: true`
    (Chart.js extends below zero on negatives), `stacked` on both axes for the
    comment breakdown, legend as today, tooltip `title` →
    `weeklyTooltipText(...)` style: *Semaine du 15 sept. 2026* (+
    ` (semaine en cours)` on the last bar), and one `label` line per series with
    the value.
  - Module-level registry `Map<container, { kind: 'line'|'multi', chart,
    payload }>`. `mountLineChartContainer` / `mountMultiLineChartContainer`
    record into it and create the chart in the current mode.
  - `currentMode()` — reads `document.querySelector('[data-segmented-control][data-name="statistics-graph-mode"]')?.dataset.value`;
    `'weekly'` only if that exact value, else `'cumulative'`.
  - `renderAll(mode)` — for each registered container: `chart.destroy()`, then
    re-create in `mode`. A container with an empty weekly payload (e.g.
    `stat-card`) always renders cumulative.
  - A `window` listener on `segmented-control-change`: ignore unless
    `detail.name === 'statistics-graph-mode'`; if the mode differs from the
    last rendered mode, `renderAll(detail.value)`.
  - The `statisticsChartMounted` guard stays so `mountAll()` called twice by
    the page script does not double-mount.
  - If importing Chart.js breaks under happy-dom, the test file uses
    `vi.mock('chart.js', …)`; do not split the module just for tests.
- `app/Domains/Statistics/Private/Resources/views/pages/admin/index.blade.php`
  (modify) — above `<x-shared::tabs>` (below the tiles, which stay untouched):
  ```blade
  <x-shared::segmented-control
      name="statistics-graph-mode"
      :label="__('statistics::admin.graph_mode_label')"
      :options="[
          'cumulative' => __('statistics::admin.graph_mode_cumulative'),
          'weekly' => __('statistics::admin.graph_mode_weekly'),
      ]"
      selected="cumulative"
      storage-key="statistics.admin.graph-mode"
  />
  ```
- `app/Domains/Statistics/README.md` (modify) — extend "Chart display and
  resampling": weekly view via `getWeeklyTimeSeries()` / `WeeklyAggregator`
  (Monday-based UTC weeks, net deltas, current week included), the
  `data-weekly-points` payload, and that `charts.js` re-creates charts on the
  `statistics-graph-mode` segmented control. No link to `docs/Feature_Planning`.

**Tests.**
- `app/Domains/Statistics/Private/Resources/js/charts.test.js` (vitest):
  - `it('formats weekly points as one bar per week labelled with the Monday')`
  - `it('gives the last bar a lighter colour than the others')`
  - `it('keeps negative weekly values')`
  - `it('formats the comment breakdown as one bar dataset per series')`
  - `it('builds the tooltip title "Semaine du <date> : <value>"')`
  - `it('appends the current-week label for the last bar')`
  - `it('keeps the existing cumulative formatter output unchanged')`
  - `it('reads the initial mode from the control data-value, defaulting to cumulative')`
    (only if `currentMode` is exported; otherwise covered in VERIFY)
- `app/Domains/Statistics/Tests/Feature/Admin/StatisticsControllerTest.php`
  (modify, add):
  - `it('renders the graph mode switch with both options, cumulative selected')`
    — asserts `role="radiogroup"`, `data-name="statistics-graph-mode"`, both
    lang keys, `statistics.admin.graph-mode` storage key.
  - `it('keeps the summary tiles outside the graph mode switch')` — the tiles
    render before the radiogroup.

**Acceptance.**
- ✅ First visit (empty `localStorage`): *Cumulé* selected, charts identical to
  today.
- ✅ Clicking *Par semaine* turns every chart on Utilisateurs, Contenus and
  Commentaires into bars, including tabs not open at the time of the click.
- ✅ Comment breakdown in weekly mode: stacked bars whose total height equals
  the *Commentaires* weekly bar for the same week.
- ✅ Last bar lighter; its tooltip reads *Semaine du {date} : {n} (semaine en
  cours)*.
- ✅ Reload after choosing *Par semaine* opens in *Par semaine*; clicking
  *Cumulé* restores the curves; tiles never change.
- ✅ Arrow keys move the selection on the switch; Tab reaches it once.
- ✅ Access unchanged: confirmed user redirected to dashboard, guest to login.
- ✅ `pnpm run gate` green (with asset build).
- ✅ `pnpm run e2e` not required (no e2e spec is added — the browser behaviour
  is covered by the visual QA checklist below).

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. Log in as an admin (and once as a
tech-admin) on `/admin/statistics`, with seeded or recomputed statistics.

| Surface | Check | OK? |
|---------|-------|-----|
| Page, first visit (cleared `localStorage`) | Switch above the tabs shows *Cumulé* selected; charts look exactly as before; tiles unchanged | |
| Switch → *Par semaine*, Utilisateurs tab | Chart becomes bars, one per week, x labels are Mondays, no gaps (zero weeks visible as empty slots) | |
| Contenus tab after switching on Utilisateurs | Histoires / Chapitres / Mots already in bars on first open (hidden-tab redraw), correctly sized | |
| Commentaires tab, weekly | *Commentaires* bars + *racines et réponses* stacked bars; stacked height matches the *Commentaires* bar of the same week; legend shows both series | |
| Current week bar | Last bar visibly lighter on every chart; tooltip *Semaine du … : n (semaine en cours)* | |
| Tooltip, a normal week | *Semaine du 15 sept. 2026 : 37* format, French month abbreviation; one line per series on the stacked chart | |
| Negative week (e.g. *Mots* after an edit removing words, or a deleted story) | Bar drops below the zero line, axis extends below zero | |
| Reload in *Par semaine* | Page opens in *Par semaine*; no long cumulative flash before the bars | |
| Switch back → *Cumulé* | All curves restored on all tabs, identical to first visit | |
| Keyboard | Tab lands on the switch once; arrow keys change the selection and redraw; visible focus ring | |
| Screen-reader semantics (devtools accessibility tree) | `radiogroup` with a label, two `radio`s, `aria-checked` follows the selection | |
| Empty metric (fresh DB, no data) | Dashed "no data" box in both modes, no JS error in the console | |
| `localStorage` blocked (private window with storage disabled, or a corrupt value set manually) | Page opens on *Cumulé*, switch still works, no console error | |
| Mobile width (~375 px) | Switch fits and is tappable above the tabs; bar charts readable within the existing admin layout | |
| Tech-admin role | Same page, same switch and behaviour as admin | |
| Non-admin (`user-confirmed`) | Still redirected away from `/admin/statistics` | |

## Open items

1. **Phase 4 — Chart.js under happy-dom.** Not verified that importing
   `charts.js` (which imports `chart.js` and `chartjs-adapter-date-fns` and
   auto-mounts at module load) runs cleanly in vitest's happy-dom. Fallback
   written into the phase: `vi.mock('chart.js', …)`. Resolve at the start of
   Phase 4 with a one-line import test.
2. **Phase 4 — initial-mode ordering (plan-level choice, not a new decision).**
   Architecture §4.4 says the initial mode comes from the control's init event.
   Checked in code: `app.js` (Alpine) loads in the head and `charts.js` at the
   end of the body, both as modules, so that event fires before `charts.js`
   listens. The plan closes the gap by having the control mirror its value in
   `data-value` (Phase 2) and `charts.js` read it at mount (Phase 4). This is
   an addition to the §4.1 contract, not a change to it — flagging it so DESIGN
   intent can be confirmed.
3. **Phase 1 — signature detail.** Architecture §3.2 types `$scopeId` as
   `?int`; the sibling methods use `mixed`. The plan uses `mixed` for
   consistency. Cosmetic, no behaviour difference.
4. **Phase 3 — comment breakdown alignment.** Architecture §4.3 defines replies
   = weekly total − weekly roots but not how to align two metrics whose first
   week can differ. The plan aligns by week (`periodStart`) and treats a
   missing root week as 0. No decision re-opened.
