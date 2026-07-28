# Statistics Domain

Centralized infrastructure for computing, storing, and displaying aggregate metrics across the platform. Statistics are **precomputed asynchronously** from domain events so page loads never run expensive counts or aggregations. The domain owns both computation and display: other domains embed Blade components rather than calling a public API.

**Not done.** The per-user surface was never delivered: `UserTotalStories`, `UserTotalChapters` and `UserTotalWords` are computed but displayed nowhere, no `x-statistics::` component is rendered outside the admin page, and `Private/Resources/lang/fr/profile.php` holds five translation keys nothing uses. The three per-user comment definitions the design called for do not exist.

**Open risks.** `DECIMAL(20,4)` may be overkill for counts (`BIGINT` would do), and recompute assumes today's event payloads — there is no answer for backfilling across a changed event shape.

## Purpose and scope

**In scope:**
- Registering extensible statistic types via the `StatisticDefinition` contract
- Storing current values (`statistic_snapshots`) and historical data (`statistic_time_series`)
- Incremental updates driven by domain events, with full recompute/backfill via event replay
- Admin statistics page (`/admin/statistics`) for global metrics
- Reusable display components (`x-statistics::*`) embeddable by other domains (e.g. Profile)

**Out of scope:**
- A `StatisticsPublicApi` for other domains to query values — display is delegated to Blade components
- Real-time/on-demand computation at request time
- Per-entity statistics beyond what is explicitly defined (story-level stats are future work)

## Key concepts

### Statistic keys and scopes

Each statistic has a stable string key following `{scope}.{metric}` (e.g. `global.total_users`, `user.total_words`). A **scope** identifies who or what the value applies to:

| Scope type | `scope_id` | Example |
|------------|------------|---------|
| `global` | `null` | Platform-wide totals |
| `user` | user ID | Per-author metrics |

Additional scope types (e.g. `story`) are supported by the schema but not yet used in Phase 1.

### Snapshots vs time-series

- **Snapshots** hold the current computed value for a `(statistic_key, scope_type, scope_id)` tuple.
- **Time-series** hold daily data points with optional `cumulative_value` for charting trends.

Time-series rows are created lazily: a user's stats only exist once an event affecting that user has been processed (or after an explicit recompute). Missing data is represented as `null`; UI components render a placeholder (`—`) rather than zero.

### StatisticDefinition plugin pattern

Each metric is a class implementing `StatisticDefinition`. The class declares:

- Its key, scope type, and human label
- Which domain events it listens to (`listensTo()`)
- Whether it maintains time-series history
- How to apply incremental deltas from an event (`computeDelta()`)
- How to rebuild from scratch (`recompute()`)

New statistics are registered in `StatisticsServiceProvider::registerStatistics()`. The registry builds an event → statistics index used by the update listener.

### Incremental updates vs full recompute

**Incremental (normal path):** When a domain emits an event, `UpdateStatisticsOnEvent` (queued) calls `StatisticComputeService::applyDelta()`. Each affected definition returns `[scopeId => delta]` pairs; snapshots and daily time-series rows are incremented, then cumulative values are recalculated.

**Full recompute:** `php artisan statistics:compute {key}` calls `recompute()` on the definition. The typical flow clears existing snapshot and time-series rows for the scope, replays stored events from the Events domain in chronological order, and rebuilds both tables. This is the same mechanism used for initial backfill after deployment.

### Chart display and resampling

Raw time-series data is stored at **daily** granularity. Charts do not plot every daily point directly — `StatisticQueryService::getChartTimeSeries()` passes daily points through `TimeSeriesResampler`, which buckets them into at most 48 evenly spaced points over the selected date range. Charts are rendered with Chart.js (bundled via Vite in `Private/Resources/js/charts.js`), mounted through `data-statistics-line-chart` attributes rather than inline Alpine components.

## Architecture decisions

### No public API

Unlike Notification or Comment, Statistics deliberately exposes **no query API** to other domains. Consumers embed self-contained Blade components that resolve `StatisticQueryService` internally. This keeps aggregation logic and null-handling centralized and prevents other domains from duplicating statistic keys or scope rules.

### Event-driven, not poll-driven

Counts are maintained incrementally from domain events (`UserRegistered`, `ChapterCreated`, `CommentPosted`, etc.) rather than by querying source tables on each page view. Full recomputes replay the Events domain audit log, so statistics stay consistent with historical activity even when incremental updates were missed.

### Multi-author story metrics

Chapter and word counts for user-scoped statistics attribute deltas to **all authors** of a story (via `StoryPublicApi::getAuthorIds()`). A co-authored story increments each author's stats equally. Global-scoped variants aggregate a single platform-wide delta.

### Queued listener

`UpdateStatisticsOnEvent` implements `ShouldQueue`, so statistic updates run asynchronously and do not block the HTTP request that emitted the event. Ensure a queue worker is running in environments where stats must stay current.

## Statistic catalogue (Phase 1)

### Global statistics (admin page)

| Key | Events | Status |
|-----|--------|--------|
| `global.total_users` | `Auth.UserRegistered`, `Auth.UserDeleted` | Implemented |
| `global.total_stories` | `Story.StoryCreated`, `Story.StoryDeleted` | Planned |
| `global.total_chapters` | `Story.ChapterCreated`, `Story.ChapterDeleted`, `Story.StoryDeleted` | Planned |
| `global.total_words` | `Story.ChapterCreated`, `Story.ChapterDeleted`, `Story.ChapterUpdated`, `Story.StoryDeleted` | Planned |
| `global.total_comments` | `Comment.Posted` | Planned |
| `global.total_root_comments` | `Comment.Posted` (root only) | Planned |

### Per-user statistics

| Key | Events | Display | Status |
|-----|--------|---------|--------|
| `user.total_stories` | `Story.StoryCreated`, `Story.StoryDeleted` | Profile (future) | Implemented |
| `user.total_chapters` | Chapter/story lifecycle events | Profile (future) | Implemented |
| `user.total_words` | Chapter/story lifecycle events | Profile (future) | Implemented |
| `user.root_comments_written` | `Comment.Posted` | Profile comment stats | Planned |
| `user.total_comments_written` | `Comment.Posted` | Profile comment stats | Planned |
| `user.root_comments_received` | `Comment.Posted` | Profile comment stats | Planned |

## Display components

Components live under `Private/Resources/views/components/` and are registered with the `statistics::` namespace:

| Component | Purpose |
|-----------|---------|
| `x-statistics::digit` | Formatted number display (`number` or `compact` format); shows `—` for null |
| `x-statistics::line-chart` | Time-series line chart (Chart.js); empty state when no data |
| `x-statistics::stat-card` | Digit + expandable inline chart for a single statistic key |

Additional admin-specific components (`stat-summary`, `stat-widget`, `comment-summary`, etc.) and the Profile embed component (`profile-comment-stats`) are defined in the feature plan and admin view but may still be under development.

Admin page: `/admin/statistics` (roles: `admin`, `tech-admin`), registered in `AdminNavigationRegistry`.

## Adding a new statistic

1. Create a class implementing `StatisticDefinition` in `Private/Definitions/{Global|User}/`.
2. Declare `listensTo()` using event **name strings** (e.g. `UserRegistered::name()`).
3. Implement `computeDelta()` returning `[scopeId => delta]` or `null` when the event is irrelevant.
4. Implement `recompute()` — use the `RecomputesStatisticFromEvents` trait when replay-from-events is sufficient; delegate delta logic to a support class (see `StoryContentDeltaCalculator`, `CommentDeltaCalculator`) when multiple statistics share event parsing rules.
5. Register the class in `StatisticsServiceProvider::registerStatistics()`.
6. If the statistic listens to new event types, add them to `registerEventListeners()` in the same provider.
7. Backfill: `./vendor/bin/sail artisan statistics:compute {key}` (add `--scope-id=` for user-scoped stats).

## Cross-domain delegation

| Concern | Delegated to | Why |
|---------|--------------|-----|
| Event emission and replay | **Events** (`EventBus`, `EventPublicApi`) | Single audit log; statistics rebuild by replaying stored events |
| Author resolution for story metrics | **Story** (`StoryPublicApi::getAuthorIds()`) | Statistics must not query Story tables directly |
| Comment counting rules | **Comment** events (`CommentPosted`) | Delta logic uses event payload, not Comment models |
| User lifecycle counts | **Auth** events (`UserRegistered`, `UserDeleted`) | User table is owned by Auth |
| Admin navigation | **Administration** (`AdminNavigationRegistry`) | Consistent admin panel structure |

Statistics tables have **no foreign keys** to `users`, stories, or other cross-domain tables. Scope IDs are plain integers; orphaned rows after entity deletion are acceptable and corrected on the next recompute.

## Privacy (future)

Users will be able to hide profile statistics via a Settings integration. Global admin statistics remain visible to administrators regardless.

## Console commands

```bash
# Recompute one statistic (optionally for a specific user scope)
./vendor/bin/sail artisan statistics:compute global.total_users
./vendor/bin/sail artisan statistics:compute user.total_words --scope-id=123
```

## Testing

Domain test helpers in `Tests/helpers.php` provide `getStatisticValue()`, `getTimeSeriesValue()`, `backfillStatistic()`, `resetStatistics()`, and `recomputeStatistic()` for feature and unit tests.
