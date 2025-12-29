{{--
    Scrollable tabs component with horizontal scroll and navigation arrows.
    
    Props:
    - tabs: array of tab definitions, each with:
        - key: unique identifier
        - label: display text
        - url: (optional) URL for link-based tabs
        - icon: (optional) Material Symbols icon name
    - activeTab: key of the currently active tab
    - mode: 'link' (default) for anchor-based navigation, 'button' for JS-based
    - onTabClick: (optional) Alpine.js expression for button mode, receives tab key
    
    Usage (link mode - Profile style):
    <x-shared::scrollable-tabs :tabs="$tabs" :active-tab="$activeTab" />
    
    Usage (button mode - Settings style):
    <x-shared::scrollable-tabs 
        :tabs="$tabs" 
        :active-tab="$activeTab" 
        mode="button"
        on-tab-click="switchTab"
    />
--}}
@props([
    'tabs' => [],
    'activeTab' => null,
    'mode' => 'link',
    'onTabClick' => 'switchTab',
])

<div class="relative" x-data="{
    el: null,
    canPrev: false,
    canNext: false,
    updateBounds() {
        if (!this.el) return;
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
}" x-init="el = $refs.tabScroller; updateBounds(); el?.addEventListener('scroll', () => updateBounds()); window.addEventListener('resize', () => updateBounds())">
    {{-- Left scroll button --}}
    <button type="button"
        x-show="canPrev"
        x-on:click="scrollByAmount(-1)"
        aria-label="{{ __('shared::components.scrollable_tabs.previous') }}"
        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 h-8 w-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-accent">
        <span class="material-symbols-outlined text-lg">chevron_left</span>
    </button>

    <nav x-ref="tabScroller" 
        class="surface-primary text-on-surface flex w-full gap-4 text-2xl font-semibold overflow-x-auto tabs-scroll-hide px-8" 
        role="tablist">
        @foreach($tabs as $tab)
            @if($mode === 'link' && isset($tab['url']))
                <a href="{{ $tab['url'] }}"
                    role="tab"
                    aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
                    class="shrink-0 flex-1 whitespace-nowrap py-3 px-1 border-b-2 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 flex items-center justify-center gap-2 {{ $activeTab === $tab['key'] ? 'selected border-none font-extrabold' : 'border-transparent' }}">
                    @if(isset($tab['icon']))
                        <span class="material-symbols-outlined">{{ $tab['icon'] }}</span>
                    @endif
                    {{ $tab['label'] }}
                </a>
            @else
                <button type="button"
                    @click="{{ $onTabClick }}('{{ $tab['key'] }}')"
                    role="tab"
                    :aria-selected="activeTab === '{{ $tab['key'] }}'"
                    :class="activeTab === '{{ $tab['key'] }}' ? 'selected border-none font-extrabold' : 'border-transparent'"
                    class="shrink-0 flex-1 whitespace-nowrap py-3 px-1 border-b-2 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 flex items-center justify-center gap-2">
                    @if(isset($tab['icon']))
                        <span class="material-symbols-outlined">{{ $tab['icon'] }}</span>
                    @endif
                    {{ $tab['label'] }}
                </button>
            @endif
        @endforeach
    </nav>

    {{-- Right scroll button --}}
    <button type="button"
        x-show="canNext"
        x-on:click="scrollByAmount(1)"
        aria-label="{{ __('shared::components.scrollable_tabs.next') }}"
        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 h-8 w-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-accent">
        <span class="material-symbols-outlined text-lg">chevron_right</span>
    </button>
</div>

<style>
    .tabs-scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .tabs-scroll-hide::-webkit-scrollbar { display: none; }
</style>
