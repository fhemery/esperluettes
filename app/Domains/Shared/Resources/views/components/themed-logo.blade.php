@props(['variant' => 'small'])

@php
    $logoPath = $variant === 'full' ? $theme->logoFull() : $theme->logo();
@endphp

<img src="{{ asset($logoPath) }}" alt="{{ config('app.name') }}" {{ $attributes }} />