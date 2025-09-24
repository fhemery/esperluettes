@props([
    'badgeColor' => 'accent',
    'popoverPlacement' => 'top',
    'popoverMaxWidth' => '20rem',
])

@php
    $slotContent = trim($slot);
@endphp

<div
    x-data="badgeOverflow({ badgeColor: @js($badgeColor) })"
    x-init="init()"
    class="relative w-full"
>
    <div x-ref="display" class="flex flex-nowrap items-center gap-2 overflow-hidden">
        <template x-if="initialized">
            <template x-for="item in shown" :key="item.key">
                <span x-html="item.html"></span>
            </template>
        </template>

        <template x-if="initialized && hidden.length">
            <x-shared::popover :placement="$popoverPlacement" :maxWidth="$popoverMaxWidth">
                <x-slot name="trigger">
                    <x-shared::badge :color="$badgeColor" size="xs" class="cursor-pointer">
                        <span class="sr-only" x-text="hiddenLabel"></span>
                        <span aria-hidden="true">+<span x-text="hidden.length"></span></span>
                    </x-shared::badge>
                </x-slot>
                <div class="flex flex-wrap gap-2">
                    <template x-for="item in hidden" :key="item.key">
                        <span x-html="item.html"></span>
                    </template>
                </div>
            </x-shared::popover>
        </template>

        <div x-ref="fallback" x-show="!initialized" class="contents">
            {!! $slotContent !!}
        </div>
    </div>

    <div x-ref="source" class="hidden" aria-hidden="true">
        {!! $slotContent !!}
    </div>

    <div x-ref="measure" class="fixed -left-[9999px] -top-[9999px] flex flex-nowrap items-center gap-2" aria-hidden="true"></div>
    <x-shared::badge
        x-ref="plusSizer"
        :color="$badgeColor"
        size="xs"
        class="fixed -left-[9999px] -top-[9999px] opacity-0 pointer-events-none select-none"
        aria-hidden="true"
    >+0</x-shared::badge>

    <noscript>
        <div class="flex flex-nowrap items-center gap-2 overflow-hidden">
            {!! $slotContent !!}
        </div>
    </noscript>
</div>

@once
    @push('scripts')
        <script>
            if (typeof window.badgeOverflow !== 'function') {
                window.badgeOverflow = function ({ badgeColor = 'accent' }) {
                    return {
                        badgeColor,
                        initialized: false,
                        shown: [],
                        hidden: [],
                        hiddenLabel: '',
                        items: [],
                        _resizeHandler: null,
                        _resizeTimeout: null,
                        _observer: null,

                        init() {
                            this.collectItems();
                            this.refreshHiddenLabel();
                            this.$nextTick(() => {
                                this.measure();
                            });
                            this.setupListeners();
                            return () => this.destroy();
                        },

                        collectItems() {
                            const children = Array.from(this.$refs.source.children)
                                .filter((el) => el.nodeType === Node.ELEMENT_NODE);
                            this.items = children.map((el, index) => ({
                                key: index,
                                html: el.outerHTML.trim(),
                                text: el.textContent.trim(),
                                width: 0,
                            }));
                            this.$refs.source.innerHTML = '';
                        },

                        setupListeners() {
                            this._resizeHandler = () => {
                                if (this._resizeTimeout) {
                                    clearTimeout(this._resizeTimeout);
                                }
                                this._resizeTimeout = setTimeout(() => this.measure(), 120);
                            };
                            window.addEventListener('resize', this._resizeHandler);
                            if (window.ResizeObserver) {
                                this._observer = new ResizeObserver(this._resizeHandler);
                                this._observer.observe(this.$el);
                            }
                        },

                        measure() {
                            if (!this.items.length) {
                                this.shown = [];
                                this.hidden = [];
                                this.refreshHiddenLabel();
                                return;
                            }

                            const display = this.$refs.display;
                            const availableWidth = this.getAvailableWidth();

                            if (!availableWidth) {
                                this.initialized = false;
                                this.$nextTick(() => this.measure());
                                return;
                            }

                            const style = window.getComputedStyle(display);
                            const gap = parseFloat(style.columnGap || style.gap || '0');

                            this.items.forEach((item) => {
                                item.width = this.measureWidth(item.html);
                            });

                            let usedWidth = 0;
                            const shownIndexes = [];
                            const hiddenIndexes = [];

                            this.items.forEach((item, index) => {
                                if (shownIndexes.length === 0) {
                                    if (item.width <= availableWidth) {
                                        shownIndexes.push(index);
                                        usedWidth = item.width;
                                    } else {
                                        hiddenIndexes.push(index);
                                    }
                                    return;
                                }

                                const nextWidth = usedWidth + gap + item.width;
                                if (nextWidth <= availableWidth) {
                                    shownIndexes.push(index);
                                    usedWidth = nextWidth;
                                } else {
                                    hiddenIndexes.push(index);
                                }
                            });

                            if (hiddenIndexes.length) {
                                let plusWidth = this.measurePlus(hiddenIndexes.length);
                                let gapBeforePlus = shownIndexes.length ? gap : 0;

                                while (shownIndexes.length && (usedWidth + gapBeforePlus + plusWidth) > (availableWidth + 0.5)) {
                                    const removedIndex = shownIndexes.pop();
                                    if (shownIndexes.length) {
                                        usedWidth -= gap + this.items[removedIndex].width;
                                    } else {
                                        usedWidth = 0;
                                    }
                                    hiddenIndexes.unshift(removedIndex);
                                    plusWidth = this.measurePlus(hiddenIndexes.length);
                                    gapBeforePlus = shownIndexes.length ? gap : 0;
                                }
                            }

                            this.shown = shownIndexes.map(index => this.items[index]);
                            this.hidden = hiddenIndexes.map(index => this.items[index]);
                            this.refreshHiddenLabel();
                            this.initialized = true;
                        },

                        getAvailableWidth() {
                            const widths = [];

                            if (this.$refs.display) {
                                const displayRect = this.$refs.display.getBoundingClientRect();
                                if (displayRect.width) {
                                    widths.push(displayRect.width);
                                }
                            }

                            if (this.$el) {
                                const elRect = this.$el.getBoundingClientRect();
                                if (elRect.width) {
                                    widths.push(elRect.width);
                                }
                            }

                            const parent = this.$el?.parentElement;
                            if (parent) {
                                const parentRect = parent.getBoundingClientRect();
                                if (parentRect.width) {
                                    widths.push(parentRect.width);
                                }
                            }

                            return widths.length ? Math.max(...widths) : 0;
                        },

                        measureWidth(html) {
                            const template = document.createElement('template');
                            template.innerHTML = html;
                            const node = template.content.firstElementChild;
                            if (!node) {
                                return 0;
                            }
                            this.$refs.measure.appendChild(node);
                            const width = node.getBoundingClientRect().width;
                            this.$refs.measure.removeChild(node);
                            return width;
                        },

                        measurePlus(count) {
                            const el = this.$refs.plusSizer;
                            el.textContent = `+${count}`;
                            const rect = el.getBoundingClientRect();
                            return rect.width;
                        },

                        refreshHiddenLabel() {
                            if (!this.hidden.length) {
                                this.hiddenLabel = '';
                                return;
                            }

                            if (this.hidden.length === 1) {
                                this.hiddenLabel = this.hidden[0].text || '';
                                return;
                            }

                            this.hiddenLabel = this.hidden
                                .map(item => item.text)
                                .filter(Boolean)
                                .join(', ');
                        },

                        destroy() {
                            if (this._resizeHandler) {
                                window.removeEventListener('resize', this._resizeHandler);
                            }
                            if (this._observer) {
                                this._observer.disconnect();
                            }
                            if (this._resizeTimeout) {
                                clearTimeout(this._resizeTimeout);
                            }
                        },
                    };
                };
            }
        </script>
    @endpush
@endonce
