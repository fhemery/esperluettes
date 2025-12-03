@props([
    'value' => 0,
    'bg' => 'bg-bg',
    'color' => 'bg-accent',
    'height' => 'h-6',
    'labelPosition' => 'right', // right | bottom
    'label' => null, // custom label, defaults to percent
])

@php
    $percent = max(0, min(100, (int) $value));
    $displayLabel = $label ?? "{$percent}%";
    $isBottom = $labelPosition === 'bottom';
@endphp

<div class="flex-1 min-w-[7rem] sm:min-w-[10rem] flex {{ $isBottom ? 'flex-col gap-1' : 'gap-2 items-center' }}">
<div
    role="progressbar"
    aria-valuenow="{{ $percent }}"
    aria-valuemin="0"
    aria-valuemax="100"
    {{ $attributes->except('id')->merge(['class' => "w-full border border-fg rounded-sm overflow-hidden $bg"]) }}
>
    <div class="{{ $color }} {{ $height }}" style="width: {{ $percent }}%"></div>
</div>
<div class="text-sm {{ $isBottom ? 'text-center' : '' }}">{{ $displayLabel }}</div>
</div>
