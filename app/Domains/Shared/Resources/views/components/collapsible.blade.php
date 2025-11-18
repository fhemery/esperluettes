@props([
'title' => '',
'open' => false,
'color' => 'accent',
'textColor' => null
])

@php($textColor = $textColor ?? $color)

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="w-full overflow-visible border-{{ $color }} border">
    <button type="button"
        class="w-full flex items-center justify-between px-4 py-2 text-left"
        x-on:click="open = !open">
        <span class="text-{{ $textColor }} font-medium" x-ref="title">{{ $title }}</span>
        <span class="material-symbols-outlined text-{{ $color }} transition-transform duration-200" 
              :class="open ? 'rotate-180' : ''" 
              x-text="'expand_less' "></span>
    </button>
    <div class="border-t border-{{ $color }} " 
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2">
        <div class="p-2 sm:p-4 text-{{ $textColor }}">
            {{ $slot }}
        </div>
    </div>
</div>