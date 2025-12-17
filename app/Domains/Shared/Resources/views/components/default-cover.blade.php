@props([
    'color' => 'tertiary',
    'title' => 'Default cover',
])

@php
    $colorMap = [
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'tertiary' => 'text-tertiary',
    ];

    $colorClass = $colorMap[$color] ?? $colorMap['tertiary'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center {$colorClass}"]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" width="150" height="200" viewBox="0 0 150 200" role="img" aria-label="{{ $title }}" title="{{ $title }}" class="w-full h-full">
        <rect x="2" y="2" width="146" height="196" rx="12" fill="currentColor" fill-opacity="0.08" stroke="currentColor" stroke-width="4"/>
        <text x="75" y="105" text-anchor="middle" font-family="Georgia, serif" font-size="90" fill="currentColor" aria-hidden="true">&amp;</text>
    </svg>
</span>
