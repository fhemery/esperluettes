<?php

namespace App\Domains\Statistics\Private\Services;

use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Public\DTOs\StatisticValue;
use App\Domains\Statistics\Public\DTOs\TimeSeriesPoint;
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
}
