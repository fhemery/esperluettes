# Admin statistics — cumulative / non-cumulative graph toggle — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** The file-by-file list of
> edits belongs to `03-plan.md`.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Statistics** owns the feature: the page, the chart components, the query
service and `charts.js` all live there. Nothing about the weekly view concerns
another domain's data.

### 1.1 Changes in other domains

**Shared** gets one new generic Blade component,
`x-shared::segmented-control` (§4.1). It knows nothing about statistics. It
is the control the planned time-range selector will reuse (DECISIONS #9).
Statistics already depends on Shared, so no new edge is needed.

## 2. Data model

### 2.1 Tables

No change. `statistic_time_series` already stores a sparse **daily delta** in
`value`, with one row per day that had events and `granularity = 'daily'`.
`cumulative_value` is only filled during a full recompute, and running sums
cover the rest. Weekly bars are derived on read from those daily deltas.

### 2.2 Model

No change.

### 2.3 Lifecycle rules

N/A — nothing is persisted. The mode lives in the browser's `localStorage`.

## 3. PHP architecture

### 3.1 Public API

None. Statistics deliberately has no public API. This feature does not add
one.

### 3.2 Services

**Weekly aggregation on read.** `StatisticQueryService` gains one method next
to `getChartTimeSeries()`:

```php
/** @return TimeSeriesPoint[]  one point per calendar week, oldest first */
public function getWeeklyTimeSeries(
    string $statisticKey,
    string $scopeType = 'global',
    ?int $scopeId = null,
): array;
```

Contract:

- One point per calendar week, **Monday → Sunday, app timezone (UTC, as the
  daily buckets already are)**. The first point is the week containing the
  first daily row, and the last is the week containing `now()`.
- `periodStart` = the Monday, `granularity = 'weekly'`, `value` = sum of the
  daily deltas in that week (net, may be negative), `cumulativeValue = null`.
- Weeks with no daily rows yield `value = 0` (no gaps).
- No daily rows → `[]` (the existing empty state takes over).
- Σ `value` over all points equals the metric's cumulative total (spec §4.2.3).

The grouping is a pure function over the daily rows, `WeeklyAggregator`
in `Private/Support/`, beside `TimeSeriesResampler`. It is done in PHP,
not SQL: the daily rows number a few hundred per metric, and PHP avoids a
`YEARWEEK` dialect split between MySQL in production and SQLite in tests.
`TimeSeriesResampler` and the cumulative path are **not touched**. The
cumulative view keeps its 48 evenly spaced buckets.

There is no cap on the number of weeks. The page shows every week since the
first data point, as the spec requires. The time-range follow-up is what will
bound it.

### 3.3 Policy / authorization

Unchanged. The existing route guard is `role:admin,tech-admin`. There is no
new route or endpoint.

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

None. The page is still `GET /admin/statistics`, and its controller still
renders the view with no data. Components keep pulling data themselves.

## 4. Frontend architecture

### 4.1 `x-shared::segmented-control` (Shared)

It is a generic, single-choice button group:

- Props: `options` (`value => label`), `selected` (initial value), `name` /
  `aria-label`, and optionally `storageKey`.
- Markup: a `role="radiogroup"` container of `role="radio"` buttons with
  `aria-checked`. There is one tab stop, and arrow keys move the selection
  (WAI-ARIA radio-group pattern). Styling uses the existing Tailwind tokens,
  in the same visual family as `x-shared::tabs`.
- Behaviour, in Alpine and kept small:
  - Selecting an option dispatches a window event,
    `segmented-control-change`, with `{ name, value }`.
  - With `storageKey`, the component reads the initial value from
    `localStorage` and writes each change back. Every access is wrapped in
    try/catch, and anything unreadable or unknown falls back to `selected`
    (spec §4.3).
  - On init, it dispatches the resolved value once so listeners can sync.

### 4.2 Page

`pages/admin/index.blade.php` puts the control above the tabs with options
`cumulative` → *Cumulé* and `weekly` → *Par semaine*, default `cumulative` and
storage key `statistics.admin.graph-mode`. The tiles are untouched.

### 4.3 Chart components — both datasets up front

Each graph ships **both** datasets in its data attributes. The cumulative
points are what they are today, and the weekly points come from
`getWeeklyTimeSeries()`. The switch then redraws in place, with no reload and
no request (DECISIONS #8).

- `line-chart` / `stat-widget`: gain a weekly point set alongside the
  existing one.
- `comment-breakdown-chart`: the weekly series are **roots = weekly
  `total_root_comments`** and **replies = weekly `total_comments` − weekly
  roots**, not clamped because nets may be negative (DECISIONS #7). The
  stacked height therefore equals the *Commentaires* weekly bar (spec §4.2.5).
  The cumulative path keeps its current `max(0, …)` computation.
- Empty state: unchanged. A metric with no data renders the dashed "no data"
  box in both modes, because both datasets are empty together.

### 4.4 `charts.js`

- Mounting keeps the current data-attribute contract and adds the weekly
  payload. Mounted charts are recorded in a module-level registry (canvas →
  `{ chart, datasets }`), which does not exist today.
- A listener on `segmented-control-change` (filtered on the control's `name`)
  **destroys and re-creates** every registered chart in the new mode. Chart.js
  4 cannot change a chart's type (line ↔ bar) in place. This covers charts in
  hidden tabs too (spec §4.1.2).
- Weekly rendering:
  - Bar chart on a category axis labelled with the Monday.
  - Stacked for the comment breakdown.
  - `beginAtZero` still applies, and the axis extends below zero when a value
    is negative.
  - The last bar, the current week, gets a lighter colour (same hue, reduced
    alpha).
  - The tooltip reads *Semaine du {date} : {valeur}*, one line per series,
    with *(semaine en cours)* appended for the last bar.
- The initial mode comes from the control's init event. Charts mount after
  it, or re-mount on it, so a stored *Par semaine* never flashes a cumulative
  chart for long.

### 4.5 i18n

New French strings:

- **Shared**: none, because the component takes its labels from the caller.
- **Statistics lang**:
  - mode labels *Cumulé* / *Par semaine*
  - *semaine en cours*
  - the tooltip pattern *Semaine du :date : :value*
  - the control's `aria-label`

The JS receives the tooltip strings through `data-options`, the same way
`locale` is passed today.

## 5. Deptrac

No new edge. The new code sits in Statistics (Private) and Shared, and the
Statistics → Shared edge already exists.

## 6. Testing strategy

- **Unit (PHP)**: `WeeklyAggregator`
  - Monday boundaries
  - sparse days → zero weeks
  - negative nets
  - current partial week included
  - year boundary (ISO week 1 / 52–53)
  - Σ weekly = sum of the deltas
- **Feature (PHP)**:
  - `getWeeklyTimeSeries()` against real `statistic_time_series` rows,
    including the empty metric
  - `/admin/statistics` renders the segmented control with both options
  - Each chart carries a weekly payload
  - The comment-breakdown weekly replies = total − roots, negatives
    preserved
  - Access rules unchanged (existing test)
- **Vitest (happy-dom)**:
  - `segmented-control`: default, stored value, corrupt or throwing
    `localStorage` → default, write on change, event dispatched, arrow-key
    navigation
  - `charts.js`: the weekly dataset formatter (current-week styling, tooltip
    text, stacked replies). Chart.js drawing itself is not asserted.
- **VERIFY (browser)**:
  - Toggling redraws all graphs on all three tabs
  - The mode survives a reload
  - The current-week bar looks distinct
  - Negative bars go below the axis
  - The stacked comment bars look right
  - The keyboard works on the switch

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where are weekly values computed, and how does the switch redraw? | **A** server aggregates, both datasets embedded, in-place switch · **B** JS aggregates raw daily points · **C** server renders one mode, `?mode=weekly` reload | A | Instant switch; week logic in PHP under feature tests; cumulative path untouched; payload cost negligible. B puts week/timezone logic in JS and still needs cumulative resampling; C reloads on every switch and makes per-browser memory need a cookie or redirect. Revisit if the range selector makes the page server-driven. |
| 2 | Where does the switch component live? | **A** generic `x-shared::segmented-control` · **B** partial local to Statistics | A | The planned time-range selector needs the same control; B would be copied or moved then. |
| 3 | Aggregate in SQL or PHP? *(not arbitrated — stated)* | `GROUP BY YEARWEEK` · PHP over daily rows | PHP | A few hundred rows per metric, and it avoids a MySQL/SQLite dialect split. |
| 4 | Switch charts by update or re-create? *(not arbitrated — stated)* | `chart.update()` · destroy + re-create | Re-create | Chart.js 4 cannot change type line ↔ bar in place. |
| 5 | Week timezone *(not arbitrated — stated)* | UTC (app timezone) · Europe/Paris | UTC | Daily buckets are already UTC days; a different week zone would split days. |

## 8. File layout

```
app/Domains/Shared/Resources/views/components/
└── segmented-control.blade.php
app/Domains/Shared/Resources/js/
├── segmented-control.js              (Alpine data, if not inline)
└── segmented-control.test.js
app/Domains/Statistics/Private/Support/
└── WeeklyAggregator.php
app/Domains/Statistics/Private/Resources/js/
└── charts.test.js
app/Domains/Statistics/Tests/Unit/
└── WeeklyAggregatorTest.php
app/Domains/Statistics/Tests/Feature/
└── WeeklyTimeSeriesTest.php
```

## 9. Risks acknowledged

- **Unbounded weekly bars.** Every week since the first data point is drawn.
  At several years of data the bars get thin. The time-range follow-up is the
  answer. Revisit if the chart becomes unreadable before that lands.
- **UTC weeks.** A Sunday-night event in Paris lands in the next week's bar.
  This is accepted for a trend view. Revisit if the app timezone changes.
- **`cumulative_value` is null after live increments.** This does not matter
  here because weekly uses `value` only, but it stays an existing quirk of the
  Statistics domain.
