@props([
    'statisticKey',
    'label',
    'scopeType' => 'global',
    'scopeId' => null,
    'chartHeight' => '280px',
    'from' => null,
    'to' => null,
    'maxPoints' => 48,
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $timeSeries = $queryService->getChartTimeSeries(
        $statisticKey,
        $scopeType,
        $scopeId,
        $from,
        $to,
        $maxPoints,
        cumulative: true,
    );
    $weeklySeries = $queryService->getWeeklyTimeSeries($statisticKey, $scopeType, $scopeId);

    $recentFrom = now()->subMonths(12)->startOfDay();
    $recentSeries = $queryService->getChartTimeSeries(
        $statisticKey,
        $scopeType,
        $scopeId,
        $recentFrom,
        $to,
        $maxPoints,
        cumulative: true,
    );
    $recentWeeklySeries = $queryService->getWeeklyTimeSeries($statisticKey, $scopeType, $scopeId, $recentFrom);
@endphp

<div {{ $attributes->class(['stat-widget surface-bg text-on-surface rounded-lg p-6']) }}>
    <h2 class="text-sm font-semibold text-fg/70 mb-4">
        {{ __('statistics::admin.evolution', ['metric' => $label]) }}
    </h2>

    <x-statistics::line-chart
        :data="$timeSeries"
        :weekly="$weeklySeries"
        :recent="$recentSeries"
        :recent-weekly="$recentWeeklySeries"
        :label="$label"
        :height="$chartHeight"
        cumulative
    />
</div>
