@props([
    'type' => 'info', // info|help
    'icon' => null,   // optional override
    'title' => null,
    'placement' => 'right', // right|left|top|bottom
    'maxWidth' => '20rem',
    'maxHeight' => '20rem',
    'iconClass' => '', // optional extra classes for the inner icon span
    'displayOnHover' => true,
])
@php($resolvedIcon = $icon ?? ($type === 'help' ? 'help' : 'info'))

<x-shared::popover :placement="$placement" :maxWidth="$maxWidth" :maxHeight="$maxHeight" :displayOnHover="$displayOnHover">
    <x-slot name="trigger">
        <button type="button" {{ $attributes->class('inline-flex items-center justify-center h-5 w-5 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500') }}>
            <span class="material-symbols-outlined text-[18px] leading-none {{ $iconClass }}">{{ $resolvedIcon }}</span>
        </button>
    </x-slot>
    @if($title)
        <div class="font-semibold text-gray-900 mb-1">{{ $title }}</div>
    @endif
    <div class="prose prose-sm max-w-none">
        {!! trim($slot) !== '' ? $slot : '' !!}
    </div>
</x-shared::popover>

