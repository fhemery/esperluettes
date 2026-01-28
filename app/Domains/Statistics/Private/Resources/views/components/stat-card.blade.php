@props([
    'statisticKey',
    'label',
    'format' => 'number',
    'scopeType' => 'global',
    'scopeId' => null,
    'showTimeSeries' => true,
    'timeSeriesHeight' => '200px',
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $statValue = $queryService->getValue($statisticKey, $scopeType, $scopeId);
    $timeSeries = $showTimeSeries 
        ? $queryService->getTimeSeries($statisticKey, $scopeType, $scopeId, 'daily')
        : [];
@endphp

<div 
    x-data="{ expanded: false }"
    {{ $attributes->class(['stat-card bg-white rounded-lg shadow p-4 cursor-pointer hover:shadow-md transition-shadow']) }}
    @click="expanded = !expanded"
>
    <div class="stat-card-header">
        <x-statistics::digit 
            :value="$statValue?->value" 
            :format="$format"
            :label="$label"
        />
    </div>
    
    @if($showTimeSeries && count($timeSeries) > 0)
        <div 
            x-show="expanded" 
            x-collapse
            class="stat-card-chart mt-4 border-t pt-4"
        >
            <x-statistics::line-chart 
                :data="$timeSeries" 
                :label="$label"
                :height="$timeSeriesHeight"
                cumulative
            />
        </div>
    @endif
    
    @if($showTimeSeries && count($timeSeries) > 0)
        <div class="text-center mt-2">
            <span class="text-xs text-gray-400" x-text="expanded ? '▲' : '▼'"></span>
        </div>
    @endif
</div>
