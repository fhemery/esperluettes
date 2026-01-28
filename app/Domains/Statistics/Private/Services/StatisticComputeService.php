<?php

namespace App\Domains\Statistics\Private\Services;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use Carbon\Carbon;
use DateTimeInterface;

class StatisticComputeService
{
    public function __construct(
        private readonly StatisticRegistry $registry,
    ) {}

    /**
     * Apply a delta update from an event
     */
    public function applyDelta(
        DomainEvent $event,
        ?DateTimeInterface $occurredAt = null
    ): void {
        $eventName = $event->name();
        $statisticKeys = $this->registry->getListenersForEvent($eventName);

        foreach ($statisticKeys as $key) {
            $definition = $this->registry->resolve($key);
            if ($definition === null) {
                continue;
            }

            $deltas = $definition->computeDelta($event);
            if ($deltas === null) {
                continue;
            }

            foreach ($deltas as $scopeId => $delta) {
                $this->incrementSnapshot($key, $definition::scopeType(), $scopeId ?: null, $delta);

                if ($definition::hasTimeSeries()) {
                    $this->incrementTimeSeries(
                        $key,
                        $definition::scopeType(),
                        $scopeId ?: null,
                        $delta,
                        $occurredAt ?? now()
                    );
                }
            }
        }
    }

    /**
     * Update or create a snapshot with an absolute value
     */
    public function updateSnapshot(string $statisticKey, string $scopeType, mixed $scopeId, float|int $value): void
    {
        StatisticSnapshot::updateOrCreate(
            [
                'statistic_key' => $statisticKey,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'value' => $value,
                'computed_at' => now(),
            ]
        );
    }

    /**
     * Increment a snapshot value by a delta
     */
    public function incrementSnapshot(string $statisticKey, string $scopeType, mixed $scopeId, float|int $delta): void
    {
        $snapshot = StatisticSnapshot::firstOrCreate(
            [
                'statistic_key' => $statisticKey,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'value' => 0,
                'computed_at' => now(),
            ]
        );

        $snapshot->increment('value', $delta);
        $snapshot->update(['computed_at' => now()]);
    }

    /**
     * Increment time-series value for a specific period
     */
    public function incrementTimeSeries(
        string $statisticKey,
        string $scopeType,
        mixed $scopeId,
        float|int $delta,
        DateTimeInterface $occurredAt
    ): void {
        $periodStart = Carbon::parse($occurredAt)->startOfDay();

        $record = StatisticTimeSeries::firstOrCreate(
            [
                'statistic_key' => $statisticKey,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'granularity' => 'daily',
                'period_start' => $periodStart,
            ],
            [
                'value' => 0,
                'cumulative_value' => null,
            ]
        );

        $record->increment('value', $delta);
    }

    /**
     * Recompute cumulative values for a time-series
     */
    public function recomputeCumulativeValues(string $statisticKey, string $scopeType, mixed $scopeId): void
    {
        $records = StatisticTimeSeries::query()
            ->where('statistic_key', $statisticKey)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->orderBy('period_start')
            ->get();

        $cumulative = 0;
        foreach ($records as $record) {
            $cumulative += (float) $record->value;
            $record->update(['cumulative_value' => $cumulative]);
        }
    }
}
