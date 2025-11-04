@props([
    'src' => null,
    'alt' => 'User avatar',
    'class' => 'h-10 w-10',
    'borderColor' => 'transparent',
])

@php($default = asset('images/default-avatar.svg'))
@php($resolved = $src ?: $default)

<img
    src="{{ $resolved }}"
    data-fallback="{{ $default }}"
    onerror="this.src=this.dataset.fallback;this.onerror=null;"
    alt="{{ $alt }}"
    loading="lazy"
    decoding="async"
    {{ $attributes->merge(['class' => $class . ' border-' . $borderColor . ' border-2 rounded-full object-cover']) }}
/>
