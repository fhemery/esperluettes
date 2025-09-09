@props([
    'type' => 'button',
    'color' => 'primary', // primary | accent | tertiary | success | danger | neutral
    'size' => 'md', // sm | md | lg
])

@php
    $base = 'inline-flex items-center justify-center rounded-md font-medium text-white transition ease-in-out duration-150 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed';

    // Size map
    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    // Color variants (explicit class strings so Tailwind JIT picks them up)
    $variants = [
        'primary' => 'bg-primary hover:bg-primary/90 focus:ring-primary/40',
        'accent' => 'bg-accent hover:bg-accent/90 focus:ring-accent/40',
        'tertiary' => 'bg-tertiary hover:bg-tertiary/90 focus:ring-tertiary/40',
        'success' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500/40',
        'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500/40',
        'neutral' => 'bg-white hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-500/40 text-black',
    ];
    $colorClasses = $variants[$color] ?? $variants['primary'];
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => "$base $sizeClasses $colorClasses"]) }}>
    {{ $slot }}
</button>
