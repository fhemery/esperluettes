<?php

namespace App\Domains\Statistics\Private\Definitions\Global;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;

class TotalUsersStatistic implements StatisticDefinition
{
    public function __construct(
        private readonly EventPublicApi $eventApi,
        private readonly StatisticComputeService $computeService,
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
        return true;
    }

    public static function listensTo(): array
    {
        return [
            UserRegistered::name(),
            UserDeleted::name(),
        ];
    }

    public function recompute(mixed $scopeId = null): ComputeResult
    {
        $this->clearData($scopeId);

        $events = $this->eventApi->getEventsByNames(self::listensTo());

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

                $this->computeService->incrementSnapshot(
                    self::key(),
                    self::scopeType(),
                    $deltaScopeId ?: null,
                    $delta
                );

                $this->computeService->incrementTimeSeries(
                    self::key(),
                    self::scopeType(),
                    $deltaScopeId ?: null,
                    $delta,
                    $eventDto->occurredAt()
                );

                $eventsProcessed++;
            }
        }

        $this->computeService->recomputeCumulativeValues(self::key(), self::scopeType(), $scopeId);

        $snapshot = StatisticSnapshot::query()
            ->where('statistic_key', self::key())
            ->where('scope_type', self::scopeType())
            ->where('scope_id', $scopeId)
            ->first();

        $timeSeriesCount = StatisticTimeSeries::query()
            ->where('statistic_key', self::key())
            ->where('scope_type', self::scopeType())
            ->where('scope_id', $scopeId)
            ->count();

        return new ComputeResult(
            snapshotValue: $snapshot?->value,
            timeSeriesPoints: $timeSeriesCount,
            eventsProcessed: $eventsProcessed,
        );
    }

    public function computeDelta(DomainEvent $event): ?array
    {
        if ($event instanceof UserRegistered) {
            return [null => 1];
        }

        if ($event instanceof UserDeleted) {
            return [null => -1];
        }

        return null;
    }

    public static function label(): string
    {
        return 'Total Users';
    }

    private function clearData(mixed $scopeId): void
    {
        StatisticSnapshot::query()
            ->where('statistic_key', self::key())
            ->where('scope_type', self::scopeType())
            ->where('scope_id', $scopeId)
            ->delete();

        StatisticTimeSeries::query()
            ->where('statistic_key', self::key())
            ->where('scope_type', self::scopeType())
            ->where('scope_id', $scopeId)
            ->delete();
    }
}
