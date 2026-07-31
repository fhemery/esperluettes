{{--
    The "+" insert affordance shown at the bottom of a block. Opens a small
    popover letting the author choose the type of block to insert at this
    position. Relies on the parent multiEditor Alpine scope (insertAfter, labels).
--}}
<div class="mt-2 flex justify-center" x-data="{ open: false }">
    <div class="relative">
        <button type="button" x-on:click="open = !open" @click.outside="open = false"
            class="w-7 h-7 flex items-center justify-center rounded-full border border-border text-primary hover:bg-primary/5"
            :title="labels.insert" :aria-expanded="open">
            <span class="material-symbols-outlined text-[18px]">add</span>
        </button>
        <div x-show="open" x-cloak x-transition
            class="absolute left-1/2 -translate-x-1/2 top-full mt-1 z-20 flex flex-col surface-read bg-white border border-border rounded-md shadow-md overflow-hidden">
            <button type="button" x-on:click="insertAfter($el, 'text'); open = false"
                class="px-3 py-1.5 text-sm text-left hover:bg-primary/5 flex items-center gap-2 whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">notes</span>{{ __('editor::multi.add_text') }}
            </button>
            <button type="button" x-on:click="insertAfter($el, 'image'); open = false"
                class="px-3 py-1.5 text-sm text-left hover:bg-primary/5 flex items-center gap-2 whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">image</span>{{ __('editor::multi.add_image') }}
            </button>
        </div>
    </div>
</div>
