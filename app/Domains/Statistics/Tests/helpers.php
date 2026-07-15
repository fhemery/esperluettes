<?php

use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Listeners\UpdateStatisticsOnEvent;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Services\StatisticRegistry;

/**
 * Get the current snapshot value for a statistic.
 */
function getStatisticValue(string $statisticKey, string $scopeType = 'global', mixed $scopeId = null): ?float
{
    $snapshot = StatisticSnapshot::query()
        ->where('statistic_key', $statisticKey)
        ->where('scope_type', $scopeType)
        ->where('scope_id', $scopeId)
        ->first();

    return $snapshot ? (float) $snapshot->value : null;
}

/**
 * Get the time-series value for a statistic at a specific date.
 */
function getTimeSeriesValue(
    string $statisticKey,
    string $date,
    string $scopeType = 'global',
    mixed $scopeId = null,
    string $granularity = 'daily'
): ?float {
    $record = StatisticTimeSeries::query()
        ->where('statistic_key', $statisticKey)
        ->where('scope_type', $scopeType)
        ->where('scope_id', $scopeId)
        ->where('granularity', $granularity)
        ->whereDate('period_start', $date)
        ->first();

    return $record ? (float) $record->value : null;
}

/**
 * Backfill a statistic by replaying stored events.
 * 
 * @param string $statisticKey The statistic key to backfill
 * @param string|null $fromDate Optional start date (Y-m-d format)
 * @param string|null $toDate Optional end date (Y-m-d format)
 * @return int Number of events processed
 */
function backfillStatistic(string $statisticKey, ?string $fromDate = null, ?string $toDate = null): int
{
    $registry = app(StatisticRegistry::class);
    $definition = $registry->get($statisticKey);
    
    if ($definition === null) {
        return 0;
    }

    $eventNames = $definition::listensTo();
    $eventPublicApi = app(EventPublicApi::class);
    $listener = app(UpdateStatisticsOnEvent::class);

    $processed = 0;

    foreach ($eventNames as $eventName) {
        $events = $eventPublicApi->getEventsByName($eventName);

        foreach ($events as $eventDto) {
            $occurredAt = $eventDto->occurredAt();

            if ($fromDate !== null && $occurredAt->format('Y-m-d') < $fromDate) {
                continue;
            }

            if ($toDate !== null && $occurredAt->format('Y-m-d') > $toDate) {
                continue;
            }

            $domainEvent = $eventDto->domainEvent();
            if ($domainEvent !== null) {
                $listener->handle($domainEvent, $occurredAt);
                $processed++;
            }
        }
    }

    return $processed;
}

/**
 * Reset all statistics data (snapshots and time-series).
 */
function resetStatistics(): void
{
    StatisticSnapshot::query()->delete();
    StatisticTimeSeries::query()->delete();
}

/**
 * Recompute a statistic from scratch.
 */
function recomputeStatistic(string $statisticKey, mixed $scopeId = null): \App\Domains\Statistics\Public\DTOs\ComputeResult
{
    $registry = app(StatisticRegistry::class);
    $definition = $registry->resolve($statisticKey);
    
    if ($definition === null) {
        throw new \InvalidArgumentException("Unknown statistic: {$statisticKey}");
    }
    
    return $definition->recompute($scopeId);
}
