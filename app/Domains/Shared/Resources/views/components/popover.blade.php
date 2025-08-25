@props([
    'placement' => 'right', // right|left|top|bottom
    'width' => '20rem',
])

<span class="inline-flex items-center align-middle select-none z-10 cursor-pointer">
    <span class="ml-0 relative cursor-pointer" x-data="popover" @keydown.escape.window="hoverOpen = false; pinned = false; updateOpen()">
        <span
            class="inline-flex cursor-pointer"
            role="button"
            style="cursor: pointer;"
            aria-haspopup="dialog"
            :aria-expanded="(hoverOpen || pinned) ? 'true' : 'false'"
            x-ref="trigger"
            @mouseenter="hoverOpen = true; updateOpen()"
            @mouseleave="closeWithDelay()"
            @mousedown.stop.prevent="pinned = !pinned; if (pinned) { hoverOpen = true } updateOpen()"
            @click.stop.prevent
            @blur="setTimeout(() => { if (!pinned) { hoverOpen = false; updateOpen() } }, 220)">
            {{ $trigger ?? '' }}
        </span>
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
                {{ $slot }}
            </div>
        </template>
    </span>
</span>
