<?php

namespace App\Domains\Statistics\Private\Support;

use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;

trait RecomputesStatisticFromEvents
{
    protected function recomputeFromEvents(
        EventPublicApi $eventApi,
        StatisticComputeService $computeService,
        mixed $scopeId = null,
    ): ComputeResult {
        $this->clearData($scopeId);

        $events = $eventApi->getEventsByNames(static::listensTo());

        $eventsProcessed = 0;

        foreach ($events as $eventDto) {
            $domainEvent = $eventDto->domainEvent();

            if ($domainEvent === null) {
                continue;
            }

            $deltas = $this->computeDelta($domainEvent);

            if ($deltas === null) {
                continue;
            }

            foreach ($deltas as $deltaScopeId => $delta) {
                if ($scopeId !== null && $deltaScopeId != $scopeId) {
                    continue;
                }

                $computeService->incrementSnapshot(
                    static::key(),
                    static::scopeType(),
                    $deltaScopeId ?: null,
                    $delta,
                );

                if (static::hasTimeSeries()) {
                    $computeService->incrementTimeSeries(
                        static::key(),
                        static::scopeType(),
                        $deltaScopeId ?: null,
                        $delta,
                        $eventDto->occurredAt(),
                    );
                }

                $eventsProcessed++;
            }
        }

        if (static::hasTimeSeries()) {
            $computeService->recomputeCumulativeValues(
                static::key(),
                static::scopeType(),
                $scopeId,
            );
        }

        $snapshot = StatisticSnapshot::query()
            ->where('statistic_key', static::key())
            ->where('scope_type', static::scopeType())
            ->where('scope_id', $scopeId)
            ->first();

        $timeSeriesCount = StatisticTimeSeries::query()
            ->where('statistic_key', static::key())
            ->where('scope_type', static::scopeType())
            ->where('scope_id', $scopeId)
            ->count();

        return new ComputeResult(
            snapshotValue: $snapshot?->value,
            timeSeriesPoints: $timeSeriesCount,
            eventsProcessed: $eventsProcessed,
        );
    }

    protected function clearData(mixed $scopeId): void
    {
        StatisticSnapshot::query()
            ->where('statistic_key', static::key())
            ->where('scope_type', static::scopeType())
            ->where('scope_id', $scopeId)
            ->delete();

        StatisticTimeSeries::query()
            ->where('statistic_key', static::key())
            ->where('scope_type', static::scopeType())
            ->where('scope_id', $scopeId)
            ->delete();
    }
}
