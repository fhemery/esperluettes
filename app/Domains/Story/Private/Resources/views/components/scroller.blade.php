@props([
    // Width of each slide; default matches story card width
    'slideWidth' => 230,
    'gap' => 32, // px
])

@php $gapAttribute = 'gap:' . $gap . 'px'; @endphp
<style>
    /* Hide scrollbar cross-browser for elements with .scroll-hide */
    .scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .scroll-hide::-webkit-scrollbar { display: none; }
</style>

<div x-data="{
        el: null,
        canPrev: false,
        canNext: false,
        updateBounds() {
            if (!this.el) return;
            const maxLeft = this.el.scrollWidth - this.el.clientWidth;
            this.canPrev = this.el.scrollLeft > 0;
            this.canNext = this.el.scrollLeft < (maxLeft - 1);
        },
        scrollByPage(dir = 1) {
            if (!this.el) return;
            const w = this.el.clientWidth;
            this.el.scrollBy({ left: dir * w, behavior: 'smooth' });
            // Recalculate after the smooth scroll progresses
            setTimeout(() => this.updateBounds(), 150);
        }
    }"
    x-init="el = $refs.scroller; updateBounds(); el.addEventListener('scroll', updateBounds); window.addEventListener('resize', updateBounds)"
    class="relative w-full min-w-0 overflow-hidden">

    <button type="button"
        @click="scrollByPage(-1)"
        :disabled="!canPrev"
        :aria-disabled="(!canPrev).toString()"
        aria-label="Précédent"
        class=" surface-accent text-on-surface [@media(pointer:coarse)]:hidden flex absolute left-2 sm:left-3 top-1/2 -translate-y-1/2 z-10 h-9 w-9 items-center justify-center rounded-full bg-surface/80 focus:outline-none focus:ring-2 focus:ring-accent shadow"
        :class="{ 'opacity-40 pointer-events-none': !canPrev, 'hover:bg-surface': canPrev }">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>

    <div x-ref="scroller" class="w-full overflow-x-auto overflow-y-hidden scroll-smooth scroll-hide max-w-full min-w-0">
        <div class="flex w-max" style="{{$gapAttribute}}">
            <div class="[@media(pointer:coarse)]:hidden shrink-0 w-6 sm:w-10"></div>
            {{ $slot }}
            <div class="[@media(pointer:coarse)]:hidden shrink-0 w-6 sm:w-10"></div>
        </div>
    </div>

    <button type="button"
        @click="scrollByPage(1)"
        :disabled="!canNext"
        :aria-disabled="(!canNext).toString()"
        aria-label="Suivant"
        class="surface-accent text-on-surface [@media(pointer:coarse)]:hidden flex absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 z-10 h-9 w-9 items-center justify-center rounded-full bg-surface/80 focus:outline-none focus:ring-2 focus:ring-accent shadow"
        :class="{ 'opacity-40 pointer-events-none': !canNext, 'hover:bg-surface': canNext }">
        <span class="material-symbols-outlined">chevron_right</span>
    </button>
</div>
