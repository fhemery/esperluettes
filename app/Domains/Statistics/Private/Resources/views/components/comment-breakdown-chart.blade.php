@props([
    'totalKey' => 'global.total_comments',
    'rootKey' => 'global.total_root_comments',
    'rootLabel',
    'replyLabel',
    'scopeType' => 'global',
    'scopeId' => null,
    'chartHeight' => '280px',
    'from' => null,
    'to' => null,
    'maxPoints' => 48,
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $totalSeries = $queryService->getChartTimeSeries(
        $totalKey,
        $scopeType,
        $scopeId,
        $from,
        $to,
        $maxPoints,
        cumulative: true,
    );
    $rootSeries = $queryService->getChartTimeSeries(
        $rootKey,
        $scopeType,
        $scopeId,
        $from,
        $to,
        $maxPoints,
        cumulative: true,
    );

    $replySeries = collect($totalSeries)->map(function ($totalPoint, $index) use ($rootSeries) {
        $rootPoint = $rootSeries[$index] ?? null;
        $totalCumulative = $totalPoint->cumulativeValue ?? $totalPoint->value;
        $rootCumulative = $rootPoint?->cumulativeValue ?? $rootPoint?->value ?? 0;

        return new \App\Domains\Statistics\Public\DTOs\TimeSeriesPoint(
            periodStart: $totalPoint->periodStart,
            granularity: $totalPoint->granularity,
            value: 0,
            cumulativeValue: max(0, $totalCumulative - $rootCumulative),
        );
    })->all();

    // Weekly nets, aligned on the week (not the index): roots may start later
    // than the total. Replies are not clamped — a net may be negative.
    $weeklyTotal = $queryService->getWeeklyTimeSeries($totalKey, $scopeType, $scopeId);
    $weeklyRootByWeek = collect($queryService->getWeeklyTimeSeries($rootKey, $scopeType, $scopeId))
        ->keyBy(fn ($point) => $point->periodStart->format('Y-m-d'));

    $weeklyRoot = [];
    $weeklyReply = [];
    foreach ($weeklyTotal as $totalPoint) {
        $rootValue = $weeklyRootByWeek->get($totalPoint->periodStart->format('Y-m-d'))?->value ?? 0;
        $weeklyRoot[] = new \App\Domains\Statistics\Public\DTOs\TimeSeriesPoint(
            periodStart: $totalPoint->periodStart,
            granularity: $totalPoint->granularity,
            value: $rootValue,
        );
        $weeklyReply[] = new \App\Domains\Statistics\Public\DTOs\TimeSeriesPoint(
            periodStart: $totalPoint->periodStart,
            granularity: $totalPoint->granularity,
            value: $totalPoint->value - $rootValue,
        );
    }

    $series = [
        [
            'label' => $rootLabel,
            'data' => $rootSeries,
            'weeklyData' => $weeklyRoot,
            'color' => 'rgb(99, 102, 241)',
            'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
        ],
        [
            'label' => $replyLabel,
            'data' => $replySeries,
            'weeklyData' => $weeklyReply,
            'color' => 'rgb(16, 185, 129)',
            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
        ],
    ];
@endphp

<div {{ $attributes->class(['comment-breakdown-chart surface-bg text-on-surface rounded-lg p-6']) }}>
    <h2 class="text-sm font-semibold text-fg/70 mb-4">
        {{ __('statistics::admin.comments_breakdown_chart_title') }}
    </h2>

    <x-statistics::multi-line-chart
        :series="$series"
        :height="$chartHeight"
        cumulative
        show-legend
    />
</div>
