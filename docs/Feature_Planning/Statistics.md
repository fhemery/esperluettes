# Statistics Domain - Feature Planning

## Overview

The Statistics domain provides a centralized infrastructure for computing, storing, and displaying aggregate metrics across the platform. It supports both **point-in-time values** (e.g., total users) and **historical time-series** (e.g., words written per month per user).

**Key design goals:**
- **Extensible**: New statistic types can be added without modifying core infrastructure
- **Precomputed**: Values are computed asynchronously to avoid expensive calculations on page load
- **Event-driven**: Leverages the Events domain to update stats incrementally and replay history
- **Historical**: Maintains time-series data for charts and trends
- **Centralized display**: Statistics domain owns both computation AND display via Blade components (no public API for other domains to call)

---

## Functional Summary

### Statistic Categories

#### Phase 1 Scope

1. **Global Statistics** (displayed on admin stats page)
   - Total registered users (with time-series)
   - Total published stories (with time-series)
   - Total published chapters (with time-series)
   - Total words published (with time-series)

2. **Per-User Statistics** (displayed on profile)
   - Root comments written on chapters (with time-series)
   - Total comments written
   - Root comments received on chapters (with time-series)

#### Future Scope

- Home page global stats display
- Words written per user
- Stories/chapters authored per user
- Reading list size
- Story-level statistics (views, comments per story)

### Display Components

- **Digit display**: Formatted numbers (e.g."12.3K")
- **Trend indicators**: Up/down arrows with percentage change
- **Line/area charts**: Time-series data (cumulative, monthly breakdown)
- **Pie/doughnut charts**: Distribution data
- **Data tables**: Detailed breakdowns with sorting/filtering

### Privacy

- Users can hide their profile statistics via a setting (future Settings integration)
- Global statistics are always public
- Admin has access to all statistics

### Architectural Decision: No Public API

Unlike Notifications or Events domains which expose a `PublicApi` for other domains to call, Statistics is **self-contained**:

- Other domains embed Statistics Blade components (e.g., `<x-statistics::profile-stats :user-id="$userId" />`)
- Statistics domain handles all data fetching internally
- This keeps statistics logic centralized and avoids spreading responsibility across domains
- Components handle their own loading states and null values

---

## Technical Architecture

### Domain Structure

```
Statistics/
├── Public/
│   ├── Contracts/
│   │   ├── StatisticDefinition.php      # Interface for defining a statistic
│   │   └── StatisticScope.php           # Enum: GLOBAL, USER, ENTITY
│   ├── DTOs/
│   │   ├── StatisticValue.php           # Current value + metadata
│   │   └── TimeSeriesPoint.php          # (timestamp, value) pair
│   └── Providers/
│       └── StatisticsServiceProvider.php
├── Private/
│   ├── Definitions/                      # Statistic type definitions
│   │   ├── Global/
│   │   │   ├── TotalUsersStatistic.php
│   │   │   ├── TotalStoriesStatistic.php
│   │   │   └── TotalWordsStatistic.php
│   │   └── User/
│   │       ├── UserWordsWrittenStatistic.php
│   │       └── UserStoriesCountStatistic.php
│   ├── Models/
│   │   ├── StatisticSnapshot.php        # Current values
│   │   └── StatisticTimeSeries.php      # Historical data points
│   ├── Services/
│   │   ├── StatisticRegistry.php        # Registers all statistic definitions
│   │   ├── StatisticComputeService.php  # Computes/updates values
│   │   └── StatisticQueryService.php    # Reads values for display
│   ├── Listeners/
│   │   └── UpdateStatisticsOnEvent.php  # Generic event handler
│   ├── Console/
│   │   ├── ComputeAllStatisticsCommand.php
│   │   └── BackfillStatisticCommand.php
│   ├── Jobs/
│   │   └── ProcessStatisticUpdateJob.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── AdminStatisticsController.php  # Admin stats page
│   ├── routes.php
│   └── Resources/
│       └── views/
│           ├── components/
│           │   ├── digit.blade.php
│           │   ├── line-chart.blade.php
│           │   └── profile-comment-stats.blade.php  # Embeddable by Profile domain
│           └── admin/
│               └── index.blade.php
├── Database/
│   └── Migrations/
│       ├── YYYY_MM_DD_HHiiss_create_statistic_snapshots_table.php
│       └── YYYY_MM_DD_HHiiss_create_statistic_time_series_table.php
└── Tests/
    ├── Feature/
    └── Unit/
```

### Database Schema

#### `statistic_snapshots` table

Stores the **current value** of each statistic. One row per (statistic_key, scope_type, scope_id).

```php
Schema::create('statistic_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('statistic_key');           // e.g., 'global.total_users', 'user.words_written'
    $table->string('scope_type')->default('global'); // 'global', 'user', 'story', etc.
    $table->unsignedBigInteger('scope_id')->nullable(); // NULL for global, user_id for user scope, etc.
    $table->decimal('value', 20, 4);           // Current computed value
    $table->json('metadata')->nullable();      // Optional extra data (breakdown, etc.)
    $table->timestamp('computed_at');          // When this value was last computed
    $table->timestamps();
    
    $table->unique(['statistic_key', 'scope_type', 'scope_id'], 'stat_snapshot_unique');
    $table->index(['scope_type', 'scope_id']);  // Query all stats for a scope
    $table->index('statistic_key');             // Query all scopes for a stat
});
```

#### `statistic_time_series` table

Stores **historical data points** for time-series statistics.

```php
Schema::create('statistic_time_series', function (Blueprint $table) {
    $table->id();
    $table->string('statistic_key');
    $table->string('scope_type')->default('global');
    $table->unsignedBigInteger('scope_id')->nullable();
    $table->string('granularity');             // 'daily', 'monthly'
    $table->date('period_start');              // Start of the period (day or month)
    $table->decimal('value', 20, 4);           // Value for this period
    $table->decimal('cumulative_value', 20, 4)->nullable(); // Running total up to this period
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->unique(
        ['statistic_key', 'scope_type', 'scope_id', 'granularity', 'period_start'],
        'stat_ts_unique'
    );
    $table->index(['statistic_key', 'scope_type', 'scope_id', 'granularity']);
    $table->index('period_start');
});
```

### Statistic Definition Contract

Each statistic type implements this interface:

```php
<?php

namespace App\Domains\Statistics\Public\Contracts;

use App\Domains\Events\Public\Contracts\DomainEvent;

interface StatisticDefinition
{
    /**
     * Unique identifier for this statistic (e.g., 'global.total_users')
     */
    public static function key(): string;
    
    /**
     * Scope type: 'global', 'user', 'story', etc.
     */
    public static function scopeType(): string;
    
    /**
     * Whether this statistic maintains time-series history
     */
    public static function hasTimeSeries(): bool;
    
    /**
     * Event names this statistic reacts to
     * @return string[]
     */
    public static function listensTo(): array;
    
    /**
     * Compute the full value from scratch (for initial load or recalculation)
     * @param mixed $scopeId The scope identifier (null for global)
     * @return float|int
     */
    public function computeFull(mixed $scopeId = null): float|int;
    
    /**
     * Compute incremental update from an event
     * Returns [scopeId => delta] pairs, or null if event doesn't affect this stat
     * @return array<mixed, float|int>|null
     */
    public function computeDelta(DomainEvent $event): ?array;
    
    /**
     * Human-readable label (for admin/debug)
     */
    public static function label(): string;
}
```

### Example Statistic Definition

```php
<?php

namespace App\Domains\Statistics\Private\Definitions\Global;

use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;

class TotalUsersStatistic implements StatisticDefinition
{
    public function __construct(
        private AuthPublicApi $authApi
    ) {}
    
    public static function key(): string
    {
        return 'global.total_users';
    }
    
    public static function scopeType(): string
    {
        return 'global';
    }
    
    public static function hasTimeSeries(): bool
    {
        return true; // Track user growth over time
    }
    
    public static function listensTo(): array
    {
        return ['Auth.UserRegistered'];
    }
    
    public function computeFull(mixed $scopeId = null): float|int
    {
        return $this->authApi->countUsers();
    }
    
    public function computeDelta(DomainEvent $event): ?array
    {
        if ($event instanceof UserRegistered) {
            return [null => 1]; // Global scope, +1
        }
        return null;
    }
    
    public static function label(): string
    {
        return 'Total Users';
    }
}
```

### Example Per-User Statistic (Phase 1: Comment Stats)

```php
<?php

namespace App\Domains\Statistics\Private\Definitions\User;

use App\Domains\Comment\Public\Api\CommentPublicApi;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;

class UserRootCommentsWrittenStatistic implements StatisticDefinition
{
    public function __construct(
        private CommentPublicApi $commentApi
    ) {}
    
    public static function key(): string
    {
        return 'user.root_comments_written';
    }
    
    public static function scopeType(): string
    {
        return 'user';
    }
    
    public static function hasTimeSeries(): bool
    {
        return true; // Track comments written per month
    }
    
    public static function listensTo(): array
    {
        return ['Comment.Posted'];
    }
    
    public function computeFull(mixed $scopeId = null): float|int
    {
        // Count root comments on chapters written by this user
        return $this->commentApi->countRootChapterCommentsByUser($scopeId);
    }
    
    public function computeDelta(DomainEvent $event): ?array
    {
        if (!$event instanceof CommentPosted) {
            return null;
        }
        
        // Only count root comments on chapters
        if (!$event->isRoot() || $event->commentableType() !== 'chapter') {
            return null;
        }
        
        return [$event->authorId() => 1];
    }
    
    public static function label(): string
    {
        return 'Root Comments Written (Chapters)';
    }
}
```

### Processing Architecture

#### Event-Driven Updates

```
┌─────────────┐    emit()    ┌───────────┐    dispatch    ┌─────────────────────┐
│ Any Domain  │ ──────────►  │ EventBus  │ ─────────────► │ Laravel Event       │
└─────────────┘              └───────────┘                │ (sync dispatch)     │
                                                          └──────────┬──────────┘
                                                                     │
                                                                     ▼
                                                          ┌─────────────────────┐
                                                          │ UpdateStatistics    │
                                                          │ OnEvent (listener)  │
                                                          └──────────┬──────────┘
                                                                     │ dispatch to queue
                                                                     ▼
                                                          ┌─────────────────────┐
                                                          │ ProcessStatistic    │
                                                          │ UpdateJob (queued)  │
                                                          └──────────┬──────────┘
                                                                     │
                                                                     ▼
                                                          ┌─────────────────────┐
                                                          │ StatisticCompute    │
                                                          │ Service             │
                                                          └──────────┬──────────┘
                                                                     │
                                    ┌────────────────────────────────┴────────────────────────────────┐
                                    ▼                                                                 ▼
                          ┌─────────────────────┐                                         ┌─────────────────────┐
                          │ statistic_snapshots │                                         │ statistic_time_series│
                          │ (update value)      │                                         │ (append/update)     │
                          └─────────────────────┘                                         └─────────────────────┘
```

#### Queue Configuration

Statistics updates should run on a dedicated queue to avoid blocking notifications and other critical jobs:

```php
// config/queue.php - add 'statistics' queue
// ProcessStatisticUpdateJob uses: public $queue = 'statistics';
```

Worker command:
```bash
php artisan queue:work --queue=statistics,default
```

### Time-Series Granularity & Compression

**Strategy:**
1. **Daily granularity** for recent data (last 90 days)
2. **Monthly granularity** for older data
3. **Compression job** runs monthly to aggregate daily → monthly

```php
// Console/CompressTimeSeriesCommand.php
// - Query daily records older than 90 days
// - Group by month, sum values
// - Create/update monthly record
// - Delete daily records
```

### Blade Components

#### Digit Display

```blade
{{-- x-statistics::digit --}}
@props([
    'value',
    'format' => 'number',  // 'number', 'compact', 'words'
    'label' => null,
])

<div {{ $attributes->class(['stat-digit']) }}>
    @if($label)
        <span class="stat-label">{{ $label }}</span>
    @endif
    <span class="stat-value" data-value="{{ $value }}">
        {{ $formatted }}
    </span>
</div>
```

#### Line Chart (Chart.js + Alpine)

```blade
{{-- x-statistics::line-chart --}}
@props([
    'data',           // TimeSeriesPoint[] from PHP
    'label' => '',
    'cumulative' => false,
    'height' => '300px',
])

<div 
    x-data="lineChart(@js($chartData))"
    {{ $attributes }}
>
    <canvas x-ref="canvas" style="height: {{ $height }}"></canvas>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lineChart', (data) => ({
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    // ... Chart.js options
                }
            });
        }
    }));
});
</script>
@endPushOnce
```

### Compute Commands

```bash
# Recompute specific statistic
php artisan statistics:compute global.total_users

# Recompute for specific scope
php artisan statistics:compute user.words_written --scope-type=user --scope-id=123
```

**Compute behavior:**
- For **non-time-series statistics**: computes current value directly (e.g., count users in DB)
- For **time-series statistics**: clears existing data, replays all relevant events chronologically to rebuild both the snapshot and historical time-series data

This unified approach means:
1. Clear existing snapshot and time-series data
2. For time-series: query events by name(s) and replay them in order
3. For each event, call `computeDelta()` and update both snapshot and time-series
4. Recompute cumulative values for charts

---

## Integration Examples

### Admin Statistics Page

A dedicated page in Statistics domain at `/admin/statistics` displaying global stats with clickable time-series:

```blade
{{-- Statistics/Private/Resources/views/admin/index.blade.php --}}
<x-administration::layout>
    <h1>{{ __('statistics::admin.title') }}</h1>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-statistics::stat-card 
            statistic-key="global.total_users"
            :label="__('statistics::admin.users')" 
        />
        <x-statistics::stat-card 
            statistic-key="global.total_stories"
            :label="__('statistics::admin.stories')" 
        />
        <x-statistics::stat-card 
            statistic-key="global.total_chapters"
            :label="__('statistics::admin.chapters')" 
        />
        <x-statistics::stat-card 
            statistic-key="global.total_words"
            :label="__('statistics::admin.words')"
            format="compact" 
        />
    </div>
    
    {{-- Modal/expandable area for time-series when card is clicked --}}
    <div x-data="{ activeChart: null }">
        <template x-if="activeChart">
            <x-statistics::line-chart-modal />
        </template>
    </div>
</x-administration::layout>
```

### Profile Page - Comment Stats

Profile domain embeds a self-contained Statistics component:

```blade
{{-- In Profile domain view --}}
<x-statistics::profile-comment-stats :user-id="$profile->user_id" />
```

The component handles all data fetching internally:

```blade
{{-- Statistics/Private/Resources/views/components/profile-comment-stats.blade.php --}}
@props(['userId'])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    
    $rootCommentsWritten = $queryService->getValue('user.root_comments_written', 'user', $userId);
    $totalCommentsWritten = $queryService->getValue('user.total_comments_written', 'user', $userId);
    $rootCommentsReceived = $queryService->getValue('user.root_comments_received', 'user', $userId);
    
    $writtenSeries = $queryService->getTimeSeries('user.root_comments_written', 'user', $userId);
    $receivedSeries = $queryService->getTimeSeries('user.root_comments_received', 'user', $userId);
@endphp

<div class="profile-comment-stats">
    @if($rootCommentsWritten === null && $totalCommentsWritten === null)
        <p class="text-muted">{{ __('statistics::profile.no_data') }}</p>
    @else
        <div class="stats-grid">
            <x-statistics::digit :value="$rootCommentsWritten?->value" :label="__('statistics::profile.root_comments_written')" />
            <x-statistics::digit :value="$totalCommentsWritten?->value" :label="__('statistics::profile.total_comments_written')" />
            <x-statistics::digit :value="$rootCommentsReceived?->value" :label="__('statistics::profile.root_comments_received')" />
        </div>
        
        @if($writtenSeries)
            <x-statistics::line-chart :data="$writtenSeries" :label="__('statistics::profile.comments_written_over_time')" />
        @endif
        
        @if($receivedSeries)
            <x-statistics::line-chart :data="$receivedSeries" :label="__('statistics::profile.comments_received_over_time')" />
        @endif
    @endif
</div>
```

---

## Implementation Steps

### Phase 1: Core Infrastructure
1. Create Statistics domain structure (folders, provider)
2. Migrations: `statistic_snapshots` and `statistic_time_series` tables
3. Models: StatisticSnapshot, StatisticTimeSeries
4. Contracts: StatisticDefinition interface
5. Services: StatisticRegistry, StatisticComputeService, StatisticQueryService
6. Provider: StatisticsServiceProvider (register services, load migrations, routes)

### Phase 2: Display Components
7. Install Chart.js via npm, configure Vite bundling
8. Blade component: `x-statistics::digit` (formatted number display)
9. Blade component: `x-statistics::line-chart` (Chart.js + Alpine)
10. Blade component: `x-statistics::stat-card` (digit + clickable for time-series)

### Phase 3: Admin Page - Global Statistics
11. AdminStatisticsController + routes (`/admin/statistics`)
12. Admin index view with stat cards grid
13. TotalUsersStatistic definition (listens to Auth.UserRegistered)
14. TotalStoriesStatistic definition (listens to Story.StoryPublished)
15. TotalChaptersStatistic definition (listens to Story.ChapterPublished)
16. TotalWordsStatistic definition (listens to Story.ChapterPublished)
17. Console command: `statistics:compute` (compute specific or all stats)
18. Backfill command: `statistics:backfill` (replay events for time-series)

### Phase 4: Event Integration
19. Listener: UpdateStatisticsOnEvent (subscribes to registered events)
20. Job: ProcessStatisticUpdateJob (queued processing)
21. Configure statistics queue

### Phase 5: Profile Comment Statistics
22. UserRootCommentsWrittenStatistic (listens to Comment.Posted)
23. UserTotalCommentsWrittenStatistic (listens to Comment.Posted)
24. UserRootCommentsReceivedStatistic (listens to Comment.Posted)
25. Blade component: `x-statistics::profile-comment-stats`
26. Integrate component in Profile domain view

### Phase 6: Testing & Polish
27. Feature tests for admin page
28. Feature tests for profile stats component
29. Unit tests for statistic definitions
30. Backfill existing data via console commands

---

## Decisions Made

1. **Chart.js bundling**: Bundle with Vite (no CDN)
2. **Null handling**: Return null, UI components handle display of missing data
3. **Time-series creation**: Lazy - created on first backfill, not eagerly for all users

## Open Questions

1. **Decimal precision**: Using DECIMAL(20,4) for flexibility. Is this overkill for counts? Could use BIGINT instead.

2. **Event versioning**: If an event's payload changes, how do we handle backfill with mixed versions?

3. **Admin page location**: Should it be `/admin/statistics` (custom route) or integrated into Filament?

---

## Future Enhancements

- **Admin dashboard**: Filament widgets showing key metrics
- **Export**: CSV/JSON export of statistics
- **Comparisons**: Compare user stats to averages
- **Goals**: User-defined goals (e.g., "write 50K words this month")
- **Webhooks**: Notify external systems on milestone achievements
