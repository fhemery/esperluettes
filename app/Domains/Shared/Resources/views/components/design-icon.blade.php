@props([
    'name',
    'color' => 'accent',
    'size' => 'md',
])

@php
    $colorMap = [
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'tertiary' => 'text-tertiary',
        'neutral' => 'text-neutral',
        'error' => 'text-error',
        'warning' => 'text-warning',
        'success' => 'text-success',
    ];

    $sizeMap = [
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16',
        'lg' => 'w-24 h-24',
    ];

    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    $colorClass = $colorMap[$color] ?? $colorMap['accent'];

    $iconFiles = [
        'butterfly' => public_path('images/icons/plant-icon-butterfly.svg'),
        'ladybug' => public_path('images/icons/plant-icon-ladybug.svg'),
        'leaf01' => public_path('images/icons/plant-icon-leaf01.svg'),
        'leaf02' => public_path('images/icons/plant-icon-leaf02.svg'),
        'leaf03' => public_path('images/icons/plant-icon-leaf03.svg'),
        'leaf04' => public_path('images/icons/plant-icon-leaf04.svg'),
        'mushroom' => public_path('images/icons/plant-icon-mushroom.svg'),
    ];

    $path = $iconFiles[$name] ?? null;
    $svg = $path && file_exists($path) ? file_get_contents($path) : null;
@endphp

@if($svg)
    <span class="inline-flex items-center justify-center {{ $colorClass }} {{ $sizeClass }}">
        {!! $svg !!}
    </span>
@endif
