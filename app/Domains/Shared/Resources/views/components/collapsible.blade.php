@props([
'title' => '',
'open' => false,
'color' => 'accent',
'textColor' => null,
'containerClasses' => '',
'headerClasses' => '',
])

@php($textColor = $textColor ?? $color)
@php($iconColor = $color === 'transparent' ? 'fg' : $color)

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="w-full overflow-visible border-{{ $color }} border">
    <button type="button"
        class="w-full flex items-center justify-between px-4 py-2 text-left"
        x-on:click="open = !open">
        @if(isset($header))
            <div class="flex-1 {{ $headerClasses }}">{{ $header }}</div>
        @else
            <span class="text-{{ $textColor }} {{ $headerClasses }}" x-ref="title">{{ $title }}</span>
        @endif
        <span class="material-symbols-outlined text-{{ $iconColor }} transition-transform duration-200" 
              :class="open ? 'rotate-180' : ''" 
              x-text="'expand_less' "></span>
    </button>
    <div class="border-t border-{{ $color }} p-2 sm:p-4 text-{{ $textColor }} {{ $containerClasses }}" 
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2">
            {{ $slot }}
    </div>
</div>