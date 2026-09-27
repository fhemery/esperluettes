@props([
    'series',
    'cumulative' => false,
    'height' => '300px',
    'showLegend' => false,
])

@php
    $chartSeries = collect($series)->map(function ($item) {
        $points = collect($item['data'])->map(fn ($point) => [
            'x' => $point->periodStart->format('Y-m-d'),
            'value' => $point->value,
            'cumulativeValue' => $point->cumulativeValue,
        ])->values()->all();

        $weeklyPoints = collect($item['weeklyData'] ?? [])->map(fn ($point) => [
            'x' => $point->periodStart->format('Y-m-d'),
            'value' => (float) $point->value,
        ])->values()->all();

        return [
            'label' => $item['label'],
            'points' => $points,
            'weeklyPoints' => $weeklyPoints,
            'color' => $item['color'] ?? 'rgb(99, 102, 241)',
            'backgroundColor' => $item['backgroundColor'] ?? 'rgba(99, 102, 241, 0.1)',
        ];
    })->values()->all();

    $allPoints = collect($chartSeries)->flatMap(fn ($item) => $item['points'])->values();
    $rangeMin = $allPoints->isNotEmpty() ? $allPoints->first()['x'] : null;
    $rangeMax = $allPoints->isNotEmpty() ? $allPoints->last()['x'] : null;

    $options = [
        'cumulative' => $cumulative,
        'stepped' => $cumulative,
        'showLegend' => $showLegend,
        'rangeMin' => $rangeMin,
        'rangeMax' => $rangeMax,
        'locale' => app()->getLocale(),
        'weekTooltip' => __('statistics::admin.week_tooltip'),
        'currentWeekLabel' => __('statistics::admin.current_week'),
    ];
@endphp

@if($allPoints->isNotEmpty())
    <div
        data-statistics-multi-line-chart
        data-series='@json($chartSeries)'
        data-options='@json($options)'
        {{ $attributes->class(['stat-multi-line-chart w-full']) }}
        style="height: {{ $height }};"
    >
        <canvas class="w-full h-full"></canvas>
    </div>
@else
    <div {{ $attributes->class(['stat-line-chart-empty text-fg/50 text-center py-8 border border-dashed border-border rounded-lg']) }}>
        {{ __('statistics::profile.no_data') }}
    </div>
@endif
