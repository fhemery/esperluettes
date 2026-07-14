@props([
    'data',
    'label' => '',
    'cumulative' => false,
    'height' => '300px',
    'granularity' => 'daily',
    'color' => 'rgb(99, 102, 241)',
    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
])

@php
    $dateFormat = match ($granularity) {
        'monthly' => 'M Y',
        default => 'd M Y',
    };

    $chartPoints = collect($data)->map(fn ($point) => [
        'label' => $point->periodStart->format($dateFormat),
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
