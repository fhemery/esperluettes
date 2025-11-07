@props([
    'value' => 0,
    'label' => null,
    'bg' => 'bg-bg',
    'color' => 'bg-accent',
    'height' => 'h-4',
])

@php
    $percent = max(0, min(100, (int) $value));
@endphp

@if($label)
    <div {{ $attributes->only('id')->merge(['class' => 'text-fg font-bold mb-1']) }}>{{ $label }}</div>
@endif

<div
    role="progressbar"
    aria-valuenow="{{ $percent }}"
    aria-valuemin="0"
    aria-valuemax="100"
    {{ $attributes->except('id')->merge(['class' => "w-full border border-fg rounded-sm overflow-hidden $bg"]) }}
>
    <div class="{{ $color }} {{ $height }}" style="width: {{ $percent }}%"></div>
</div>
