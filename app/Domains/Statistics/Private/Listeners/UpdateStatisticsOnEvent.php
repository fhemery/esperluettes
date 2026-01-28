<?php

namespace App\Domains\Statistics\Private\Listeners;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use DateTimeInterface;

class UpdateStatisticsOnEvent
{
    public function __construct(
        private readonly StatisticComputeService $computeService,
    ) {}

    public function handle(DomainEvent $event, ?DateTimeInterface $occurredAt = null): void
    {
        $this->computeService->applyDelta($event, $occurredAt);
    }
}
