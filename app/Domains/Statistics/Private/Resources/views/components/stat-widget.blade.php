@props([
    'statisticKey',
    'label',
    'format' => 'number',
    'scopeType' => 'global',
    'scopeId' => null,
    'chartHeight' => '280px',
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $statValue = $queryService->getValue($statisticKey, $scopeType, $scopeId);
    $timeSeries = $queryService->getTimeSeries($statisticKey, $scopeType, $scopeId, 'daily');
@endphp

<div {{ $attributes->class(['stat-widget surface-bg text-on-surface rounded-lg p-6 flex flex-col gap-6']) }}>
    <x-statistics::digit
        :value="$statValue?->value"
        :format="$format"
        :label="$label"
        size="xl"
        class="items-start"
    />

    <div class="stat-widget-chart border-t border-border pt-6">
        <h2 class="text-sm font-semibold text-fg/70 mb-4">
            {{ __('statistics::admin.evolution', ['metric' => $label]) }}
        </h2>

        <x-statistics::line-chart
            :data="$timeSeries"
            :label="$label"
            :height="$chartHeight"
            cumulative
            granularity="daily"
        />
    </div>
</div>
