# Statistics Domain

This domain provides centralized infrastructure for computing, storing, and displaying aggregate metrics across the platform.

## Key Features

- **Precomputed statistics**: Values are computed asynchronously to avoid expensive calculations on page load
- **Time-series history**: Maintains historical data for charts and trends
- **Event-driven updates**: Leverages the Events domain to update stats incrementally
- **Extensible**: New statistic types can be added by implementing `StatisticDefinition`

## Architecture

Statistics domain is **self-contained**: it owns both computation AND display via Blade components. Other domains embed Statistics components rather than calling a public API.

### Key Components

- `Public/Contracts/StatisticDefinition` - Interface for defining a statistic type
- `Private/Services/StatisticRegistry` - Registers all statistic definitions
- `Private/Services/StatisticComputeService` - Computes and updates values
- `Private/Services/StatisticQueryService` - Reads values for display

### Database Tables

- `statistic_snapshots` - Current values (one row per statistic/scope)
- `statistic_time_series` - Historical data points (daily/monthly granularity)

## Adding a New Statistic

1. Create a class implementing `StatisticDefinition` in `Private/Definitions/`
2. Register it in `StatisticsServiceProvider::boot()` via the registry
3. Run backfill command if historical data is needed

## Usage

Other domains embed Statistics Blade components:

```blade
{{-- Display profile comment stats --}}
<x-statistics::profile-comment-stats :user-id="$userId" />

{{-- Display a single digit --}}
<x-statistics::digit :value="$value" format="compact" />
```

See `docs/Feature_Planning/Statistics.md` for full documentation.
