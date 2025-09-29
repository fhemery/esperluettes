@props([
    // Width of each slide; default matches story card width
    'slideWidth' => 230,
    'gap' => 16, // px
])

<style>
    /* Hide scrollbar cross-browser for elements with .scroll-hide */
    .scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .scroll-hide::-webkit-scrollbar { display: none; }
</style>

<div x-data="{
        el: null,
        scrollByPage(dir = 1) {
            if (!this.el) return;
            const w = this.el.clientWidth;
            this.el.scrollBy({ left: dir * w, behavior: 'smooth' });
        }
    }"
    x-init="el = $refs.scroller"
    class="relative w-full min-w-0 overflow-hidden">

    <button type="button"
        @click="scrollByPage(-1)"
        aria-label="Précédent"
        class="[@media(pointer:coarse)]:hidden flex absolute left-2 sm:left-3 top-1/2 -translate-y-1/2 z-10 h-9 w-9 items-center justify-center rounded-full bg-surface/80 hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent shadow">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>

    <div x-ref="scroller" class="w-full overflow-x-auto overflow-y-hidden scroll-smooth scroll-hide max-w-full min-w-0">
        <div class="flex w-max" style="gap: {{ $gap }}px">
            <div class="[@media(pointer:coarse)]:hidden shrink-0 w-6 sm:w-10"></div>
            {{ $slot }}
            <div class="[@media(pointer:coarse)]:hidden shrink-0 w-6 sm:w-10"></div>
        </div>
    </div>

    <button type="button"
        @click="scrollByPage(1)"
        aria-label="Suivant"
        class="[@media(pointer:coarse)]:hidden flex absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 z-10 h-9 w-9 items-center justify-center rounded-full bg-surface/80 hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent shadow">
        <span class="material-symbols-outlined">chevron_right</span>
    </button>
</div>
