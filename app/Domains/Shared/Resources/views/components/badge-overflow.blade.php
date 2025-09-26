@props([
    'overflowColor' => 'accent',
])

@php
    $overflowId = 'badge-overflow-' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
@endphp

<!-- The BadgeOverflow script is registered in app.js because sometimes the badge belongs to an async rendered view -->
<div class="flex items-start gap-2" x-data="BadgeOverflow('{{ $overflowId }}')" x-ref="container">
    <x-shared::popover placement="top">
        <x-slot:trigger>
            <x-shared::badge color="{{ $overflowColor }}" size="xs" x-ref="countBadge">+<span x-text="count"></span></x-shared::badge>
        </x-slot:trigger>
        <div class="p-1" x-ref="overflow" id="{{ $overflowId }}"></div>
    </x-shared::popover>

    <div class="flex items-start gap-2 overflow-hidden w-full" x-ref="visible"></div>
    
    <div class="hidden" x-ref="hidden">
             {{ $slot }}
    </div>
</div>