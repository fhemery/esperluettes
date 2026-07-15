<?php

namespace App\Domains\Statistics\Private\Definitions\User;

use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Support\RecomputesStatisticFromEvents;
use App\Domains\Statistics\Private\Support\StoryContentDeltaCalculator;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\StoryDeleted;

class UserTotalChaptersStatistic implements StatisticDefinition
{
    use RecomputesStatisticFromEvents;

    public function __construct(
        private readonly EventPublicApi $eventApi,
        private readonly StatisticComputeService $computeService,
        private readonly StoryContentDeltaCalculator $deltaCalculator,
    ) {}

    public static function key(): string
    {
        return 'user.total_chapters';
    }

    public static function scopeType(): string
    {
        return 'user';
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
            StoryDeleted::name(),
        ];
    }

    public function recompute(mixed $scopeId = null): ComputeResult
    {
        return $this->recomputeFromEvents($this->eventApi, $this->computeService, $scopeId);
    }

    public function computeDelta(DomainEvent $event): ?array
    {
        return $this->deltaCalculator->forChapters($event, self::scopeType());
    }

    public static function label(): string
    {
        return 'User Total Chapters';
    }
}
