# Admin statistics — time-range selector — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

*Pre-filled at WRAP of `admin-statistics-graph-toggle` from its out-of-scope
list (spec §8, decision #1); to be rewritten by the user at REFINE.*

## What I want

A time-range selector on `/admin/statistics`, e.g. *12 derniers mois* by
default and *Depuis le début* to see everything, applying to every graph in
both the *Cumulé* and *Par semaine* modes.

## Why

The weekly view draws one bar per week since the first data point, unbounded
and not resampled, so bars get thin as history grows. A trend view also mostly
cares about the recent period.

## Constraints or ideas I already have

- Reuse `x-shared::segmented-control` (built for this purpose), placed next to
  the *Cumulé | Par semaine* switch.
- The graph-toggle design embeds both datasets in the page and switches in
  place. If the range makes the page server-driven, revisit that choice.
- `E2eStatisticsSeeder`, the `STATISTICS` e2e fixture and
  `e2e/pages/AdminStatisticsPage.ts` already exist for browser checks.

## Explicitly out of scope

—
