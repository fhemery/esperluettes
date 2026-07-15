<?php

namespace App\Domains\Statistics\Private\Definitions\Global;

use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Support\RecomputesStatisticFromEvents;
use App\Domains\Statistics\Private\Support\StoryContentDeltaCalculator;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\StoryDeleted;

class TotalWordsStatistic implements StatisticDefinition
{
    use RecomputesStatisticFromEvents;

    public function __construct(
        private readonly EventPublicApi $eventApi,
        private readonly StatisticComputeService $computeService,
        private readonly StoryContentDeltaCalculator $deltaCalculator,
    ) {}

    public static function key(): string
    {
        return 'global.total_words';
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
            ChapterCreated::name(),
            ChapterDeleted::name(),
            ChapterUpdated::name(),
            StoryDeleted::name(),
        ];
    }

    public function recompute(mixed $scopeId = null): ComputeResult
    {
        return $this->recomputeFromEvents($this->eventApi, $this->computeService, $scopeId);
    }

    public function computeDelta(DomainEvent $event): ?array
    {
        return $this->deltaCalculator->forWords($event, self::scopeType());
    }

    public static function label(): string
    {
        return 'Total Words';
    }
}
