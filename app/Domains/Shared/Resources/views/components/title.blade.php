@props([
    'icon' => null,
    'tag' => 'h1',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 font-extrabold mb-4 text-4xl text-accent']) }}>
    @if ($icon)
        <span class="material-symbols-outlined">
            {{ $icon }}
        </span>
    @endif
    <{{ $tag }}>{{ $slot }}</{{ $tag }}>
    
</div>
