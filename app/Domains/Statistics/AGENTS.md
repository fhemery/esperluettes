# Statistics Domain — Agent Instructions

- README: [app/Domains/Statistics/README.md](README.md)

## Public API

**There is no `StatisticsPublicApi`.** This is intentional — other domains must not query statistic values directly. Integration points are:

| Surface | Role |
|---------|------|
| `StatisticDefinition` | Contract for registering new statistic types |
| `x-statistics::*` Blade components | Self-contained display; fetch data via `StatisticQueryService` internally |
| `StatisticQueryService` | Read path for components and admin views — **private**; do not inject into other domains |

Do not add a public query API unless explicitly requested. If Profile or another domain needs stats, embed a Statistics Blade component.

## Contracts and DTOs

| Class | Role |
|-------|------|
| `StatisticDefinition` | Plugin interface: `key()`, `scopeType()`, `hasTimeSeries()`, `listensTo()`, `computeDelta()`, `recompute()`, `label()` |
| `StatisticValue` | Read DTO: current snapshot value + metadata |
| `TimeSeriesPoint` | Read DTO: one time-series bucket |
| `ComputeResult` | Returned by `recompute()`: snapshot value, events processed, time-series point count |

## Events emitted

This domain emits no events of its own.

## Listens to

All subscriptions are registered in `StatisticsServiceProvider::registerEventListeners()` via `EventBus::subscribe()`. The listener is `UpdateStatisticsOnEvent` (queued).

| Event | Statistics affected |
|-------|---------------------|
| `Auth::UserRegistered` | `global.total_users`; user-scoped stats when applicable |
| `Auth::UserDeleted` | `global.total_users` |
| `Story::StoryCreated` | `global.total_stories`, `user.total_stories` |
| `Story::StoryDeleted` | Story/chapter/word global and user stats |
| `Story::ChapterCreated` | Chapter and word stats |
| `Story::ChapterDeleted` | Chapter and word stats |
| `Story::ChapterUpdated` | Word stats (delta from word count change) |
| `Comment::Posted` | Comment global stats (planned); user comment stats (planned) |

The listener resolves affected statistics through `StatisticRegistry::getListenersForEvent()` — keyed by event **name string** as declared in each definition's `listensTo()`.

## Non-obvious invariants

**`computeDelta()` return shape.** Returns `[scopeId => delta]` or `null`. Global scope uses `null` as the array key (e.g. `[null => 1]`). The compute service normalizes falsy scope IDs to `null` before persistence.

**Do not query source-domain tables from statistic definitions.** User counts come from Auth events; story author attribution goes through `StoryPublicApi::getAuthorIds()`. Direct Eloquent queries against other domains' tables violate architecture rules and will break under deptrac.

**Multi-author stories duplicate user-scoped deltas.** `StoryContentDeltaCalculator::authorScopeMap()` returns one entry per author with the same delta. A chapter with two authors increments both users' chapter/word stats by the full amount.

**Empty author list returns `[]`, not `null`.** When `getAuthorIds()` returns no authors, `computeDelta()` yields an empty array. No snapshot update occurs — this is distinct from `null` (event not applicable).

**Recompute always clears first.** Both `RecomputesStatisticFromEvents` and inline recompute implementations delete existing snapshot and time-series rows for the target scope before replaying events. Do not skip the clear step — partial merges produce incorrect cumulative values.

**Time-series is daily only.** `StatisticComputeService::incrementTimeSeries()` always writes `granularity = 'daily'`. Monthly compression described in the feature plan is not implemented; do not assume monthly rows exist.

**Cumulative values are recomputed after every delta.** After incrementing daily buckets, `recomputeCumulativeValues()` walks all rows in `period_start` order and rewrites `cumulative_value`. Chart display for cumulative mode relies on these stored values.

**Null means "no data yet", not zero.** `StatisticQueryService::getValue()` returns `null` when no snapshot row exists. Blade components must handle null (the `digit` component shows `—`). Do not coerce null to `0` at the query layer.

**`UpdateStatisticsOnEvent` is queued.** Implements `ShouldQueue`. Tests that assert immediate stat updates after `dispatchEvent()` depend on the sync queue driver or must call the listener/handler directly. Feature tests use `dispatchEvent()` which triggers the sync event pipeline.

**Event replay ordering matters for recompute.** `RecomputesStatisticFromEvents` calls `EventPublicApi::getEventsByNames()` which returns events sorted chronologically across all listened event types. Custom `recompute()` implementations must preserve this ordering when replaying (see `TotalUsersStatistic` for the inline pattern).

**Statistic keys are permanent once data exists.** Changing a key orphanates existing snapshot and time-series rows. Add new keys rather than renaming.

**No FK on `scope_id`.** `statistic_snapshots` and `statistic_time_series` store scope IDs as plain integers without foreign keys. User or story deletion does not cascade; negative deltas from delete events (or a later recompute) correct the counts.

**Register statistics in `StatisticsServiceProvider::boot()`.** `StatisticRegistry` is a singleton. Definitions must be registered before the application handles events. Adding a definition class without registering it silently excludes it from incremental updates.

**Support calculators are shared, not duplicated.** Story lifecycle delta parsing lives in `StoryContentDeltaCalculator`; comment parsing in `CommentDeltaCalculator`. New story- or comment-derived statistics should extend these rather than re-parse event payloads.

## Registry integrations

- **AdminNavigationRegistry** (`Administration` domain) — registers the `statistics` nav group and `/admin/statistics` page for `admin` and `tech-admin` roles.

## Adding a new statistic (checklist)

1. Implement `StatisticDefinition` under `Private/Definitions/`.
2. Use `RecomputesStatisticFromEvents` when recompute = clear + replay events; otherwise implement `recompute()` explicitly.
3. Register in `StatisticsServiceProvider::registerStatistics()`.
4. If listening to new event types, add the event class to `registerEventListeners()`.
5. Add Blade component or extend an existing one for display — do not expose values via a new Public API.
6. Write a feature test using helpers from `Tests/helpers.php`.

## Testing helpers

`Tests/helpers.php` provides:

```php
getStatisticValue(string $statisticKey, string $scopeType = 'global', mixed $scopeId = null): ?float
getTimeSeriesValue(string $statisticKey, string $date, ...): ?float
backfillStatistic(string $statisticKey, ?string $fromDate = null, ?string $toDate = null): int
resetStatistics(): void
recomputeStatistic(string $statisticKey, mixed $scopeId = null): ComputeResult
```

Call `resetStatistics()` in `beforeEach` when tests need a clean slate. Use `backfillStatistic()` to replay persisted events without going through the queue.
