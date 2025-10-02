@props([
'title' => '',
'open' => false,
'color' => 'accent'
])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="w-full mb-4 overflow-visible border-{{ $color }} border">
    <button type="button"
        class="w-full flex items-center justify-between px-4 py-2 text-left"
        @click="open = !open">
        <span class="text-{{ $color }} font-medium" x-ref="title">{{ $title }}</span>
        <span class="material-symbols-outlined text-{{ $color }}" x-text="open ? 'expand_less' : 'expand_more'"></span>
    </button>
    <div class="border-t border-{{ $color }} " x-show="open" x-transition>
        <div class="p-2 sm:p-4 text-{{ $color }}">
            {{ $slot }}
        </div>
    </div>
</div>