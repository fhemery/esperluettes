<?php

namespace App\Domains\Statistics\Private\Definitions\Global;

use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Support\CommentDeltaCalculator;
use App\Domains\Statistics\Private\Support\RecomputesStatisticFromEvents;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;

class TotalRootCommentsStatistic implements StatisticDefinition
{
    use RecomputesStatisticFromEvents;

    public function __construct(
        private readonly EventPublicApi $eventApi,
        private readonly StatisticComputeService $computeService,
        private readonly CommentDeltaCalculator $deltaCalculator,
    ) {}

    public static function key(): string
    {
        return 'global.total_root_comments';
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
            CommentPosted::name(),
        ];
    }

    public function recompute(mixed $scopeId = null): ComputeResult
    {
        return $this->recomputeFromEvents($this->eventApi, $this->computeService, $scopeId);
    }

    public function computeDelta(DomainEvent $event): ?array
    {
        return $this->deltaCalculator->forRootComments($event);
    }

    public static function label(): string
    {
        return 'Total Root Comments';
    }
}
