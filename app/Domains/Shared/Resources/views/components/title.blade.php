@props([
    'icon' => null,
    'customIcon' => null,
    'tag' => 'h1',
])

@php 
    $tag = $tag ?? 'h1';
    $classes = match($tag) {
        'h1' => 'text-4xl mb-4 font-extrabold',
        'h2' => 'text-2xl mb-2 font-semibold',
        'h3' => 'text-xl mb-2 font-semibold',
        default => 'text-lg mb-2 font-semibold',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 ' . $classes . ' text-accent']) }}>
    @if ($icon)
        <span class="material-symbols-outlined {{ $classes }}">
            {{ $icon }}
        </span>
    @endif
    @if ($customIcon)
        <x-shared::design-icon :name="$customIcon" size="sm" color="accent"/>
    @endif
    <{{ $tag }}>{{ $slot }}</{{ $tag }}>
    
</div>
