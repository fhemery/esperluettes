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
        'x' => $point->periodStart->format('Y-m-d'),
        'value' => $point->value,
        'cumulativeValue' => $point->cumulativeValue,
    ])->values()->all();

    $rangeMin = count($chartPoints) > 0 ? $chartPoints[0]['x'] : null;
    $rangeMax = count($chartPoints) > 0 ? $chartPoints[array_key_last($chartPoints)]['x'] : null;

    $options = [
        'cumulative' => $cumulative,
        'stepped' => $cumulative,
        'color' => $color,
        'backgroundColor' => $backgroundColor,
        'rangeMin' => $rangeMin,
        'rangeMax' => $rangeMax,
        'locale' => app()->getLocale(),
    ];
@endphp

@if(count($chartPoints) > 0)
    <div
        data-statistics-line-chart
        data-points='@json($chartPoints)'
        data-label="{{ $label }}"
        data-options='@json($options)'
        {{ $attributes->class(['stat-line-chart w-full']) }}
        style="height: {{ $height }};"
    >
        <canvas class="w-full h-full"></canvas>
    </div>
@else
    <div {{ $attributes->class(['stat-line-chart-empty text-fg/50 text-center py-8 border border-dashed border-border rounded-lg']) }}>
        {{ __('statistics::profile.no_data') }}
    </div>
@endif
