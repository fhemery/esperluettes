@props([
    'type' => 'info', // info|help
    'icon' => null,   // optional override
    'title' => null,
    'placement' => 'right', // right|left|top|bottom
    'width' => '20rem',
])
@php($resolvedIcon = $icon ?? ($type === 'help' ? 'help' : 'info'))
<span class="inline-flex items-center align-middle select-none z-10">
    <span class="ml-1 relative" x-data="popover" @keydown.escape.window="hoverOpen = false; pinned = false; updateOpen()">
        <button type="button"
                class="inline-flex items-center justify-center h-5 w-5 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                aria-haspopup="dialog"
                :aria-expanded="(hoverOpen || pinned) ? 'true' : 'false'"
                x-ref="trigger"
                @mouseenter="hoverOpen = true; updateOpen()"
                @mouseleave="closeWithDelay()"
                @mousedown.stop.prevent="pinned = !pinned; if (pinned) { hoverOpen = true } updateOpen()"
                @click.stop.prevent
                @blur="setTimeout(() => { if (!pinned) { hoverOpen = false; updateOpen() } }, 220)">
            <span class="material-symbols-outlined text-[18px] leading-none">{{ $resolvedIcon }}</span>
        </button>
        <template x-teleport="body">
            <div x-cloak x-show="hoverOpen || pinned" x-transition.opacity.duration.100
                 class="fixed z-[9999] p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700"
                 role="dialog" aria-modal="true" :aria-hidden="(!open).toString()"
                 x-ref="panel"
                 :style="styleObj"
                 style="display:none"
                 x-init="init($refs.trigger, '{{ $placement }}', '{{ $width }}')"
                 x-effect="(hoverOpen || pinned) && measureAndCompute()"
                 @mouseenter="hoverOpen = true; updateOpen()"
                 @mouseleave="closeWithDelay()"
                 @click.outside="if (this.trigger && this.trigger.contains($event.target)) { return } pinned = false; hoverOpen = false; updateOpen()">
                @if($title)
                    <div class="font-semibold text-gray-900 mb-1">{{ $title }}</div>
                @endif
                <div class="prose prose-sm max-w-none">
                    {!! trim($slot) !== '' ? $slot : '' !!}
                </div>
            </div>
        </template>
    </span>
</span>

