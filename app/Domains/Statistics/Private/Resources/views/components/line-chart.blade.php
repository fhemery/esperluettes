@props([
    'data',
    'label' => '',
    'cumulative' => false,
    'height' => '300px',
    'color' => 'rgb(99, 102, 241)',
    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
])

@php
    $chartPoints = collect($data)->map(fn ($point) => [
        'label' => $point->periodStart->format('M Y'),
        'value' => $point->value,
        'cumulativeValue' => $point->cumulativeValue,
    ])->values()->all();
    
    $options = [
        'cumulative' => $cumulative,
        'color' => $color,
        'backgroundColor' => $backgroundColor,
    ];
@endphp

@if(count($chartPoints) > 0)
    <div 
        x-data="statisticsLineChart(@js($chartPoints), @js($label), @js($options))"
        {{ $attributes->class(['stat-line-chart']) }}
    >
        <canvas x-ref="canvas" style="height: {{ $height }}; width: 100%;"></canvas>
    </div>
@else
    <div {{ $attributes->class(['stat-line-chart-empty text-gray-400 text-center py-8']) }}>
        {{ __('statistics::statistics.profile.no_data') }}
    </div>
@endif
