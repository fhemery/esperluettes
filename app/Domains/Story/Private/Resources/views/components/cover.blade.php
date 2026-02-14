@props([
    'coverType' => 'default',
    'coverUrl' => null,
    'coverHdUrl' => null,
    'clickable' => false,
    'width' => 150,
    'class' => '',
])

@php
    $widthClass = match ((int) $width) {
        300 => 'w-[300px]',
        230 => 'w-[230px]',
        default => 'w-[150px]',
    };
@endphp

<div {{ $attributes->merge(['class' => "{$widthClass} {$class}"]) }} aria-hidden="true">
    @if ($coverType === 'default')
        <x-shared::default-cover class="{{ $widthClass }} object-contain" />
    @else
        <img src="{{ $coverUrl }}" alt="" class="{{ $widthClass }} object-contain" loading="lazy" />
    @endif
</div>
