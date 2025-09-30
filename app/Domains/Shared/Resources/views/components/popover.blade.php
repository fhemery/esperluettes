@props([
    'placement' => 'right', // right|left|top|bottom
    'maxWidth' => '20rem',
    'maxHeight' => '20rem',
])

<div class="flex items-center align-middle select-none z-10 cursor-pointer">
    <div class="ml-0 relative cursor-pointer" x-data="popover" x-init="mountRoot()" @keydown.escape.window="hoverOpen = false; pinned = false; updateOpen()">
        <div
            class="flex items-center leading-none cursor-pointer"
            role="button"
            style="cursor: pointer;"
            aria-haspopup="dialog"
            :aria-expanded="(hoverOpen || pinned) ? 'true' : 'false'"
            x-ref="trigger"
            @mouseenter="onTriggerEnter()"
            @mouseleave="onTriggerLeave()"
            @mousedown.stop.prevent="onTriggerMouseDown()"
            @click.stop.prevent
            @blur="onTriggerBlur()">
            {{ $trigger ?? '' }}
        </div>
        <template x-teleport="body">
            <div x-cloak x-show="hoverOpen || pinned" x-transition.opacity.duration.100
                 class="fixed z-[9999] p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700"
                 role="dialog" aria-modal="true" :aria-hidden="(!open).toString()"
                 x-ref="panel"
                 :style="styleObj"
                 style="display:none"
                 x-init="init($refs.trigger, '{{ $placement }}', '{{ $maxWidth }}', '{{ $maxHeight }}')"
                 x-effect="(hoverOpen || pinned) && measureAndCompute()"
                 @mouseenter="hoverOpen = true; updateOpen()"
                 @mouseleave="closeWithDelay()"
                 @click.outside="if (this.trigger && this.trigger.contains($event.target)) { return } pinned = false; hoverOpen = false; updateOpen()">
                {{ $slot }}
            </div>
        </template>
    </div>
</div>
