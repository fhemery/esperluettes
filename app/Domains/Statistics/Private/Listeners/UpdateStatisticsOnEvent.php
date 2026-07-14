<?php

namespace App\Domains\Statistics\Private\Listeners;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateStatisticsOnEvent implements ShouldQueue
{
    public function __construct(
        private readonly StatisticComputeService $computeService,
    ) {}

    public function handle(DomainEvent $event, ?DateTimeInterface $occurredAt = null): void
    {
        $this->computeService->applyDelta($event, $occurredAt);
    }
}
