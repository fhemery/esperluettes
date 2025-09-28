@props([
    // Visual style of the badge. Defaults to surface-primary as requested.
    // Supported: surface-primary | surface-secondary | accent | primary | tertiary | neutral
    'color' => 'surface-primary',
    // Size of the badge: sm | md
    'size' => 'sm',
    'icon' => false,
    'outline' => false,
])

@php
    $base = 'inline-flex items-center rounded-full leading-none align-middle select-none whitespace-nowrap';

    // Size map
    $sizes = [
        'xs' => 'py-1 px-1.5 text-xs font-bold',
        'sm' => 'py-[6px] px-2 text-sm font-bold',
        'md' => 'py-1.5 px-4 text-md font-bold',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['sm'];


    $variants = [
        'primary' => 'surface-primary text-on-surface border-surface',
        'secondary' => 'surface-secondary text-on-surface border-surface',
        'success' => 'surface-success text-on-surface border-surface',
        'info' => 'surface-info text-on-surface border-surface',
        'warning' => 'surface-warning text-on-surface border-surface',
        'error' => 'surface-error text-on-surface border-surface',
        'accent' => 'surface-accent text-on-surface border-surface',
        'tertiary' => 'surface-tertiary text-on-surface border-surface',
        'neutral' => 'surface-neutral text-on-surface border-surface',
    ];

    $colorClasses = $variants[$color] ?? $variants['primary'];
    if ($outline) {
        // Use a custom modifier class to avoid clashing with Tailwind's `outline` utility
        $colorClasses = $colorClasses. ' is-outline';
    }
@endphp

<span {{ $attributes->merge(['class' => "$base $sizeClasses $colorClasses" . ($icon ? ' gap-1' : '')]) }}>
    @if($icon)
        <span class="material-symbols-outlined text-{{$size}} leading-none">{{ $icon }}</span>
    @endif
    {{ $slot }}
</span>
