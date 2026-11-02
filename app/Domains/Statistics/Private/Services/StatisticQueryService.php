<?php

namespace App\Domains\Statistics\Private\Services;

use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Support\TimeSeriesResampler;
use App\Domains\Statistics\Private\Support\WeeklyAggregator;
use App\Domains\Statistics\Public\DTOs\StatisticValue;
use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
use Carbon\Carbon;
use DateTimeInterface;

class StatisticQueryService
{
    /**
     * Get current value of a statistic
     */
    public function getValue(string $statisticKey, string $scopeType = 'global', mixed $scopeId = null): ?StatisticValue
    {
        $snapshot = StatisticSnapshot::query()
            ->where('statistic_key', $statisticKey)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return new StatisticValue(
            statisticKey: $snapshot->statistic_key,
            value: (float) $snapshot->value,
            computedAt: $snapshot->computed_at,
            metadata: $snapshot->metadata,
        );
    }

    /**
     * Get multiple statistics for a scope
     * @param string[] $statisticKeys
     * @return array<string, StatisticValue|null>
     */
    public function getValues(array $statisticKeys, string $scopeType = 'global', mixed $scopeId = null): array
    {
        $snapshots = StatisticSnapshot::query()
            ->whereIn('statistic_key', $statisticKeys)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->get()
            ->keyBy('statistic_key');

        $result = [];
        foreach ($statisticKeys as $key) {
            $snapshot = $snapshots->get($key);
            $result[$key] = $snapshot ? new StatisticValue(
                statisticKey: $snapshot->statistic_key,
                value: (float) $snapshot->value,
                computedAt: $snapshot->computed_at,
                metadata: $snapshot->metadata,
            ) : null;
        }

        return $result;
    }

    /**
     * Get time-series data for a statistic
     * @return TimeSeriesPoint[]
     */
    public function getTimeSeries(
        string $statisticKey,
        string $scopeType = 'global',
        mixed $scopeId = null,
        ?string $granularity = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null
    ): array {
        $query = StatisticTimeSeries::query()
            ->where('statistic_key', $statisticKey)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->orderBy('period_start');

        if ($granularity !== null) {
            $query->where('granularity', $granularity);
        }

        if ($from !== null) {
            $query->where('period_start', '>=', $from);
        }

        if ($to !== null) {
            $query->where('period_start', '<=', $to);
        }

        return $query->get()
            ->map(fn (StatisticTimeSeries $row) => new TimeSeriesPoint(
                periodStart: $row->period_start,
                granularity: $row->granularity,
                value: (float) $row->value,
                cumulativeValue: $row->cumulative_value !== null ? (float) $row->cumulative_value : null,
            ))
            ->all();
    }

    /**
     * Get resampled time-series data suitable for chart display.
     *
     * @return TimeSeriesPoint[]
     */
    public function getChartTimeSeries(
        string $statisticKey,
        string $scopeType = 'global',
        mixed $scopeId = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        int $maxPoints = 48,
        bool $cumulative = true,
    ): array {
        $boundsQuery = StatisticTimeSeries::query()
            ->where('statistic_key', $statisticKey)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('granularity', 'daily');

        if ($from === null) {
            $firstPeriod = (clone $boundsQuery)->min('period_start');

            if ($firstPeriod === null) {
                return [];
            }

            $from = Carbon::parse($firstPeriod)->startOfDay();
        } else {
            $from = Carbon::parse($from)->startOfDay();
        }

        if ($to === null) {
            $to = now()->startOfDay();
        } else {
            $to = Carbon::parse($to)->startOfDay();
        }

        $dailyPoints = $this->getTimeSeries(
            $statisticKey,
            $scopeType,
            $scopeId,
            'daily',
            $from,
            $to,
        );

        $seed = $this->startingLevelBefore($statisticKey, $scopeType, $scopeId, $from);
        if ($seed !== null) {
            array_unshift($dailyPoints, $seed);
        }

        return app(TimeSeriesResampler::class)->resample(
            $dailyPoints,
            $from,
            $to,
            $maxPoints,
            $cumulative,
        );
    }

    /**
     * Cumulative level reached by the last daily row strictly before $from,
     * as a point the resampler can start its running total from.
     */
    private function startingLevelBefore(
        string $statisticKey,
        string $scopeType,
        mixed $scopeId,
        Carbon $from,
    ): ?TimeSeriesPoint {
        $before = StatisticTimeSeries::query()
            ->where('statistic_key', $statisticKey)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('granularity', 'daily')
            ->where('period_start', '<', $from);

        $last = (clone $before)->orderByDesc('period_start')->first();

        if ($last === null) {
            return null;
        }

        $level = $last->cumulative_value !== null
            ? (float) $last->cumulative_value
            : (float) $before->sum('value');

        return new TimeSeriesPoint(
            periodStart: $last->period_start,
            granularity: $last->granularity,
            value: 0,
            cumulativeValue: $level,
        );
    }

    /**
     * Get net deltas per calendar week (Monday → Sunday) up to the current week.
     * With $from, weeks before the one containing $from are dropped.
     *
     * @return TimeSeriesPoint[] one point per calendar week, oldest first
     */
    public function getWeeklyTimeSeries(
        string $statisticKey,
        string $scopeType = 'global',
        mixed $scopeId = null,
        ?DateTimeInterface $from = null,
    ): array {
        $points = $this->getTimeSeries($statisticKey, $scopeType, $scopeId, 'daily');
        $weeks = app(WeeklyAggregator::class)->aggregate($points, now());

        if ($from === null) {
            return $weeks;
        }

        $firstWeek = Carbon::parse($from)->startOfWeek(Carbon::MONDAY)->startOfDay();

        return array_values(array_filter(
            $weeks,
            fn (TimeSeriesPoint $week) => Carbon::parse($week->periodStart)->gte($firstWeek),
        ));
    }
}
