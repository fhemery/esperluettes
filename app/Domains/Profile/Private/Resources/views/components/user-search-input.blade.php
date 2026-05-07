@props(['name', 'value' => null, 'initialDisplayName' => null, 'initialAvatarUrl' => null, 'required' => false, 'searchUrl'])

@php $defaultAvatar = asset('images/default-avatar.svg'); @endphp

<div
    x-data="{
        query: @js($initialDisplayName ?? ''),
        selectedId: @js($value),
        selectedName: @js($initialDisplayName ?? ''),
        selectedAvatarUrl: @js($initialAvatarUrl ?? ''),
        open: false,
        loading: false,
        timer: null,
        onInput() {
            clearTimeout(this.timer);
            if (this.query.length < 2) {
                this.$refs.results.innerHTML = '';
                this.open = false;
                return;
            }
            this.timer = setTimeout(() => this.doSearch(), 300);
        },
        async doSearch() {
            this.loading = true;
            try {
                const r = await fetch(@js($searchUrl) + '?q=' + encodeURIComponent(this.query));
                const html = await r.text();
                this.$refs.results.innerHTML = html;
                this.open = this.$refs.results.innerHTML.trim() !== '';
            } finally {
                this.loading = false;
            }
        },
        select(e) {
            const btn = e.target.closest('[data-user-id]');
            if (!btn) return;
            this.selectedId = parseInt(btn.dataset.userId, 10);
            this.selectedName = btn.dataset.name;
            this.selectedAvatarUrl = btn.dataset.avatarUrl ?? '';
            this.query = this.selectedName;
            this.$refs.results.innerHTML = '';
            this.open = false;
        },
        clear() {
            this.selectedId = null;
            this.selectedName = '';
            this.selectedAvatarUrl = '';
            this.query = '';
            this.$refs.results.innerHTML = '';
            this.open = false;
        }
    }"
    class="relative"
    @click.outside="open = false"
>
    {{-- Hidden value — only submitted when a profile is selected --}}
    <template x-if="selectedId !== null">
        <input type="hidden" name="{{ $name }}" :value="selectedId">
    </template>

    {{-- Avatar overlay (left side), shown when a profile is selected --}}
    <div
        x-show="selectedId !== null"
        x-cloak
        class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none z-10"
    >
        <img
            :src="selectedAvatarUrl || @js($defaultAvatar)"
            :alt="selectedName"
            class="h-6 w-6 rounded-full object-cover flex-shrink-0"
            onerror="this.src='{{ $defaultAvatar }}'; this.onerror=null;"
        >
    </div>

    {{-- The single input — handles both search and display-of-selection states --}}
    <x-shared::text-input
        type="text"
        x-model="query"
        @input="selectedId === null && onInput()"
        @keydown.backspace="selectedId !== null && clear()"
        x-bind:readonly="selectedId !== null"
        x-bind:class="selectedId !== null ? 'pl-10 pr-9 cursor-default' : 'pr-8'"
        :required="$required"
        autocomplete="off"
        class="w-full"
        placeholder="{{ __('profile::search.placeholder') }}"
    />

    {{-- Loading indicator (right side), shown while fetching --}}
    <span
        x-show="loading && selectedId === null"
        x-cloak
        class="absolute right-3 top-1/2 -translate-y-1/2 text-fg/40 text-sm pointer-events-none"
        aria-hidden="true"
    >…</span>

    {{-- Clear button (right side), shown when a profile is selected --}}
    <button
        x-show="selectedId !== null"
        x-cloak
        type="button"
        @click="clear()"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-fg/50 hover:text-fg"
        aria-label="{{ __('profile::search.clear') }}"
    >
        <span class="material-symbols-outlined text-lg leading-none">close</span>
    </button>

    {{-- Results dropdown --}}
    <div
        x-show="open"
        x-cloak
        x-ref="results"
        @click="select($event)"
        class="absolute z-50 mt-1 w-full surface-read border border-border rounded shadow-lg max-h-64 overflow-y-auto"
    ></div>
</div>
