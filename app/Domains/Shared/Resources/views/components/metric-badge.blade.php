@props([
    'icon' => 'info',
    'value' => 0,
    'label' => '',
    'tooltip' => '',
    'class' => '', // extra classes for the trigger badge
])

@php
    $displayValue = is_numeric($value)
        ? \App\Domains\Shared\Support\NumberFormatter::compact((int)$value)
        : (string) $value;
@endphp

<x-shared::popover placement="top" maxWidth="16rem">
    <x-slot name="trigger">
        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300 {{ $class }}">
            <span class="material-symbols-outlined text-[16px] leading-none">{{ $icon }}</span>
            <span>{{ $displayValue }}</span>
        </span>
    </x-slot>
    @if($label !== '')
        <div class="font-semibold text-gray-900 text-center">{{ $label }}</div>
    @endif
    @if($tooltip !== '')
        <div class="text-gray-700 text-center">{{ $tooltip }}</div>
    @endif
</x-shared::popover>
