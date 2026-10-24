<?php

namespace App\Domains\Statistics\Private\Definitions\Global;

use App\Domains\Events\Public\Contracts\DomainEvent;
use App\Domains\Quote\Public\Api\QuotePublicApi;
use App\Domains\Quote\Public\Events\ChapterPassageQuoted;
use App\Domains\Quote\Public\Events\ChapterPassageQuoteDeleted;
use App\Domains\Quote\Public\Events\UserQuotesRemoved;
use App\Domains\Quote\Public\Events\UserQuotesRestored;
use App\Domains\Statistics\Private\Models\StatisticSnapshot;
use App\Domains\Statistics\Private\Models\StatisticTimeSeries;
use App\Domains\Statistics\Private\Services\StatisticComputeService;
use App\Domains\Statistics\Private\Support\RecomputesStatisticFromEvents;
use App\Domains\Statistics\Public\Contracts\StatisticDefinition;
use App\Domains\Statistics\Public\DTOs\ComputeResult;
use Illuminate\Support\Carbon;

class TotalQuotesStatistic implements StatisticDefinition
{
    use RecomputesStatisticFromEvents;

    public function __construct(
        private readonly QuotePublicApi $quoteApi,
        private readonly StatisticComputeService $computeService,
    ) {}

    public static function key(): string
    {
        return 'global.total_quotes';
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
            ChapterPassageQuoted::name(),
            ChapterPassageQuoteDeleted::name(),
            UserQuotesRemoved::name(),
            UserQuotesRestored::name(),
        ];
    }

    public function recompute(mixed $scopeId = null): ComputeResult
    {
        // Past deletions were never recorded as events: rebuild from the owner's table instead of replaying.
        $this->clearData($scopeId);

        $days = $this->quoteApi->getDailyNetChanges();

        foreach ($days as $day => $delta) {
            $this->computeService->incrementSnapshot(static::key(), static::scopeType(), null, $delta);
            $this->computeService->incrementTimeSeries(static::key(), static::scopeType(), null, $delta, Carbon::parse($day));
        }

        $this->computeService->recomputeCumulativeValues(static::key(), static::scopeType(), null);

        return new ComputeResult(
            snapshotValue: StatisticSnapshot::query()
                ->where('statistic_key', static::key())
                ->where('scope_type', static::scopeType())
                ->whereNull('scope_id')
                ->first()?->value,
            timeSeriesPoints: StatisticTimeSeries::query()
                ->where('statistic_key', static::key())
                ->where('scope_type', static::scopeType())
                ->whereNull('scope_id')
                ->count(),
            eventsProcessed: count($days),
        );
    }

    public function computeDelta(DomainEvent $event): ?array
    {
        return match (true) {
            $event instanceof ChapterPassageQuoted => [null => 1],
            $event instanceof ChapterPassageQuoteDeleted => [null => -1],
            $event instanceof UserQuotesRemoved => [null => -$event->count],
            $event instanceof UserQuotesRestored => [null => $event->count],
            default => null,
        };
    }

    public static function label(): string
    {
        return 'Total Quotes';
    }
}
