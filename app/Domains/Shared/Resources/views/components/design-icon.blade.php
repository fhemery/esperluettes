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

    // Seasonal icons: use numbered format (01-07)
    // $theme is injected by ResolveThemeMiddleware
    $themeName = $theme->value ?? 'autumn';
    $path = public_path("images/themes/{$themeName}/icons/design-{$name}.svg");
    $svg = file_exists($path) ? file_get_contents($path) : null;
@endphp

@if($svg)
    <span class="inline-flex items-center justify-center {{ $colorClass }} {{ $sizeClass }}">
        {!! $svg !!}
    </span>
@endif
