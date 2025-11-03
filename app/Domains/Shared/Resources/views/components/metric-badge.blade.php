@props([
    'icon' => 'info',
    'value' => 0,
    'label' => '',
    'tooltip' => '',
    'size' => 'xs',
    'color' => 'neutral',
    'outline' => false,
])

@php
    $displayValue = is_numeric($value)
        ? \App\Domains\Shared\Support\NumberFormatter::compact((int)$value)
        : (string) $value;
@endphp

<x-shared::popover placement="top" maxWidth="16rem">
    <x-slot name="trigger">
        <x-shared::badge :color="$color" :size="$size" :icon="$icon" :outline="$outline">
            {{ $displayValue }}
        </x-shared::badge>
    </x-slot>
    @if($label !== '')
        <div class="font-semibold text-gray-900 text-center">{{ $label }}</div>
    @endif
    @if($tooltip !== '')
        <div class="text-gray-700 text-center">{!! $tooltip !!}</div>
    @endif
</x-shared::popover>
