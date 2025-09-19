<div x-data="globalSearch()" x-init="init()" class="relative hidden md:block w-full max-w-md" @keydown.escape.window="close()">
    <div class="flex items-center gap-2">
        <i class="material-symbols-outlined text-accent text-2xl">search</i>
        <input
            x-model.debounce.300ms="q"
            @focus="maybeOpen()"
            @keydown.arrow-down.prevent="highlightNext()"
            @keydown.arrow-up.prevent="highlightPrev()"
            @keydown.enter.prevent="activateHighlighted()"
            type="search"
            class="bg-transparent border-b border-fg/40 focus:border-fg outline-none w-full placeholder-fg/60"
            placeholder="{{ __('search::header.label') }}"
            aria-label="{{ __('search::header.label') }}"
        />
        <template x-if="loading">
            <svg class="animate-spin h-5 w-5 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </template>
    </div>
    <div x-show="open" x-transition @mousedown.away="close()" class="absolute left-0 mt-2 w-full z-50" role="dialog" aria-live="polite">
        <div class="rounded-md shadow-lg border bg-white text-black overflow-hidden" x-ref="dropdown">
            <template x-if="html" >
                <div x-html="html"></div>
            </template>
        </div>
    </div>
    <script>
        function globalSearch() {
            return {
                q: '',
                open: false,
                loading: false,
                html: '',
                highlightedIndex: -1,
                init() {
                    this.$watch('q', (value) => {
                        const q = (value || '').trim();
                        if (q.length < 2) { this.html=''; this.open=false; return; }
                        this.fetchResults(q);
                    });
                },
                maybeOpen() { if (this.html) this.open = true; },
                close() { this.open = false; this.highlightedIndex=-1; },
                async fetchResults(q) {
                    this.loading = true;
                    try {
                        const res = await fetch(`/search/partial?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const html = await res.text();
                        this.html = html;
                        this.open = true;
                        this.$nextTick(() => { if (window.Alpine && Alpine.initTree) Alpine.initTree(this.$refs.dropdown); });
                        this.resetHighlight();
                    } catch (e) { console.error(e); }
                    finally { this.loading = false; }
                },
                resetHighlight() { this.highlightedIndex = -1; },
                options() {
                    return this.$refs.dropdown ? Array.from(this.$refs.dropdown.querySelectorAll('li[role=\"option\"]')) : [];
                },
                highlightNext() {
                    const opts = this.options(); if (!opts.length) return;
                    this.highlightedIndex = Math.min(opts.length - 1, this.highlightedIndex + 1);
                    opts[this.highlightedIndex].classList.add('bg-neutral-100');
                    if (this.highlightedIndex > 0) opts[this.highlightedIndex - 1].classList.remove('bg-neutral-100');
                },
                highlightPrev() {
                    const opts = this.options(); if (!opts.length) return;
                    this.highlightedIndex = Math.max(0, this.highlightedIndex - 1);
                    opts[this.highlightedIndex].classList.add('bg-neutral-100');
                    if (this.highlightedIndex + 1 < opts.length) opts[this.highlightedIndex + 1].classList.remove('bg-neutral-100');
                },
                activateHighlighted() {
                    const opts = this.options(); if (this.highlightedIndex < 0 || this.highlightedIndex >= opts.length) return;
                    const el = opts[this.highlightedIndex];
                    const href = el.getAttribute('onclick')?.match(/'(.*)'/);
                    if (href && href[1]) { window.location.href = href[1]; }
                }
            }
        }
    </script>
</div>
