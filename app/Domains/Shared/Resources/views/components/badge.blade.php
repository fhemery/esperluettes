@props([
    // Visual style of the badge. Defaults to surface-primary as requested.
    // Supported: surface-primary | surface-secondary | accent | primary | tertiary | neutral
    'color' => 'surface-primary',
    // Size of the badge: sm | md
    'size' => 'sm',
])

@php
    $base = 'inline-flex items-center rounded-md leading-none align-middle select-none whitespace-nowrap';

    // Size map
    $sizes = [
        'xs' => 'p-1.5 text-xs font-bold',
        'sm' => 'p-2.5 text-sm font-bold',
        'md' => 'p-3.5 text-md font-bold',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['sm'];


    $variants = [
        'primary' => 'surface-primary text-on-surface',
        'secondary' => 'surface-secondary text-on-surface',
        'success' => 'surface-success text-on-surface',
        'info' => 'surface-info text-on-surface',
        'warning' => 'surface-warning text-on-surface',
        'error' => 'surface-error text-on-surface',
        'accent' => 'surface-accent text-on-surface',
        'tertiary' => 'surface-tertiary text-on-surface',
        'neutral' => 'surface-neutral text-on-surface',
    ];

    $colorClasses = $variants[$color] ?? $variants['primary'];
@endphp

<span {{ $attributes->merge(['class' => "$base $sizeClasses $colorClasses"]) }}>{{ $slot }}</span>
