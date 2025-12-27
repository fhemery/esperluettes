@props([
    // Array of tabs: [['key' => 'about', 'label' => 'About', 'disabled' => false]]
    'tabs' => [],
    // Initial active tab key
    'initial' => null,
    // Optional extra classes for the nav container
    'navClass' => '',
    'color' => 'neutral',
    'tracking' => false,
    // When true, tabs become horizontally scrollable with arrows on overflow
    'scrollable' => false,
])

@php
    $tabs = collect($tabs)->map(function ($t) {
        return array_merge([
            'key' => '',
            'label' => '',
            'disabled' => false,
        ], (array) $t);
    })->values();
    $initialKey = $initial ?? ($tabs->first()['key'] ?? '');
@endphp

<style>
    .tabs-scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .tabs-scroll-hide::-webkit-scrollbar { display: none; }
</style>

<div
    x-data="{
        tab: @js($initialKey),
        tracking: @js((bool) $tracking),
        scrollable: @js((bool) $scrollable),
        keys: @js($tabs->pluck('key')->values()),
        el: null,
        canPrev: false,
        canNext: false,
        setFromHash() {
            const k = window.location.hash ? window.location.hash.substring(1) : '';
            if (this.keys.includes(k)) this.tab = k;
        },
        updateBounds() {
            if (!this.scrollable || !this.el) return;
            const maxLeft = this.el.scrollWidth - this.el.clientWidth;
            this.canPrev = this.el.scrollLeft > 0;
            this.canNext = this.el.scrollLeft < (maxLeft - 1);
        },
        scrollByAmount(dir = 1) {
            if (!this.el) return;
            const w = this.el.clientWidth * 0.8;
            this.el.scrollBy({ left: dir * w, behavior: 'smooth' });
            setTimeout(() => this.updateBounds(), 150);
        }
    }"
    x-init="
        if (tracking) { setFromHash(); window.addEventListener('hashchange', () => setFromHash()); }
        if (scrollable) { 
            el = $refs.tabScroller; 
            updateBounds(); 
            el?.addEventListener('scroll', () => updateBounds()); 
            window.addEventListener('resize', () => updateBounds()); 
        }
    "
    class="flex-1 w-full"
>
    <div class="relative">
        @if($scrollable)
        {{-- Left scroll button --}}
        <button type="button"
            x-show="scrollable && canPrev"
            x-on:click="scrollByAmount(-1)"
            aria-label="Précédent"
            class="absolute left-0 top-1/2 -translate-y-1/2 z-10 h-8 w-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            <span class="material-symbols-outlined text-lg">chevron_left</span>
        </button>
        @endif

        <nav 
            @if($scrollable) x-ref="tabScroller" @endif
            class="surface-{{$color}} text-on-surface flex w-full gap-4 {{ $navClass }} {{ $scrollable ? 'overflow-x-auto tabs-scroll-hide px-8' : '' }}" 
            role="tablist" 
            aria-label="Tabs"
        >
            @foreach($tabs as $t)
                @php($key = (string) $t['key'])
                @php($label = (string) $t['label'])
                @php($disabled = (bool) ($t['disabled'] ?? false))
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === @js($key) ? 'true' : 'false'"
                    :tabindex="tab === @js($key) ? '0' : '-1'"
                    @click="if (!{{ $disabled ? 'true' : 'false' }}) { tab = @js($key); if (tracking) history.replaceState(null, '', '#' + @js($key)); }"
                    @keydown.arrow-right.prevent="
                        const buttons = Array.from($el.parentElement.querySelectorAll('button[role=\'tab\']'));
                        const i = buttons.indexOf($el);
                        const next = buttons[(i + 1) % buttons.length];
                        next.focus(); next.click();
                    "
                    @keydown.arrow-left.prevent="
                        const buttons = Array.from($el.parentElement.querySelectorAll('button[role=\'tab\']'));
                        const i = buttons.indexOf($el);
                        const prev = buttons[(i - 1 + buttons.length) % buttons.length];
                        prev.focus(); prev.click();
                    "
                    class="{{ $scrollable ? 'shrink-0' : 'flex-1' }} whitespace-nowrap py-3 px-1 border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    :class="tab === @js($key)
                        ? 'selected border-none font-extrabold'
                        : 'border-transparent {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}'"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        @if($scrollable)
        {{-- Right scroll button --}}
        <button type="button"
            x-show="scrollable && canNext"
            x-on:click="scrollByAmount(1)"
            aria-label="Suivant"
            class="absolute right-0 top-1/2 -translate-y-1/2 z-10 h-8 w-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-accent">
            <span class="material-symbols-outlined text-lg">chevron_right</span>
        </button>
        @endif
    </div>

    <div class="">
        {{ $slot }}
    </div>
</div>
