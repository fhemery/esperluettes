@props([
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'flex gap-2 font-extrabold mb-4 text-4xl text-accent']) }}>
    @if ($icon)
        <span class="material-symbols-outlined">
            {{ $icon }}
        </span>
    @endif
    <h1>{{ $slot }}</h1>
    
</div>
