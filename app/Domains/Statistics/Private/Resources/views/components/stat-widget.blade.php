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
@endphp

<div {{ $attributes->class(['stat-widget surface-bg text-on-surface rounded-lg p-6']) }}>
    <h2 class="text-sm font-semibold text-fg/70 mb-4">
        {{ __('statistics::admin.evolution', ['metric' => $label]) }}
    </h2>

    <x-statistics::line-chart
        :data="$timeSeries"
        :label="$label"
        :height="$chartHeight"
        cumulative
    />
</div>
