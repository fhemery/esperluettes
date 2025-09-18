@props([
    'icon' => 'info',
    'value' => 0,
    'label' => '',
    'tooltip' => '',
])

@php
    $displayValue = is_numeric($value)
        ? \App\Domains\Shared\Support\NumberFormatter::compact((int)$value)
        : (string) $value;
@endphp

<x-shared::popover placement="top" maxWidth="16rem">
    <x-slot name="trigger">
        <x-shared::badge color="neutral" size="xs">
            <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px] leading-none">{{ $icon }}</span>
                <span>{{ $displayValue }}</span>
            </div>
        </x-shared::badge>
    </x-slot>
    @if($label !== '')
        <div class="font-semibold text-gray-900 text-center">{{ $label }}</div>
    @endif
    @if($tooltip !== '')
        <div class="text-gray-700 text-center">{{ $tooltip }}</div>
    @endif
</x-shared::popover>
