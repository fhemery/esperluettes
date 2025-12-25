@props([
    // Initial selections for preload
    'initialUserIds' => [], // array<int>
    'initialRoleSlugs' => [], // array<string>
    // If true, only search profiles (no roles)
    'profilesOnly' => false,
])

<div x-data="profileRolePicker()" x-init="init({ initialUserIds: @js($initialUserIds), initialRoleSlugs: @js($initialRoleSlugs), profilesOnly: @js($profilesOnly) })" class="w-full">
    <div class="relative">
        <input
            type="search"
            x-model.debounce.300ms="q"
            @focus="openIfAny()"
            @keydown.arrow-down.prevent="highlightNext()"
            @keydown.arrow-up.prevent="highlightPrev()"
            @keydown.enter.prevent="activateHighlighted()"
            placeholder="{{ __('profile::picker.placeholder') }}"
            class="w-full border border-accent px-3 py-2 focus:ring-2 focus:ring-accent/40 focus:border-accent"
            aria-label="{{ __('profile::picker.placeholder') }}"
        />
        <template x-if="loading">
            <svg class="animate-spin h-5 w-5 text-accent absolute right-2 top-2.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </template>

        <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white text-black border rounded-md shadow" @click.outside="close()" role="listbox">
            <div class="max-h-72 overflow-auto" x-ref="dropdown">
                <template x-if="results.profiles.length">
                    <div>
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500">{{ __('profile::picker.section_profiles') }}</div>
                        <ul>
                            <template x-for="(p, idx) in results.profiles" :key="'p-'+p.id">
                                <li role="option" @mouseenter="highlightIndex = visibleIndex(idx, 'profile')" @click="selectProfile(p)"
                                    :class="{'bg-neutral-100': isHighlighted(visibleIndex(idx, 'profile'))}"
                                    class="px-3 py-2 cursor-pointer flex items-center gap-3">
                                    <x-shared::avatar :src="''" class="h-7 w-7" :alt="__('profile::picker.section_profiles')" x-bind:src="p.avatar_url"></x-shared::avatar>
                                    <span x-text="p.display_name"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                <template x-if="!profilesOnly && results.roles.length">
                    <div>
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500">{{ __('profile::picker.section_roles') }}</div>
                        <ul>
                            <template x-for="(r, idx) in results.roles" :key="'r-'+r.slug">
                                <li role="option" @mouseenter="highlightIndex = visibleIndex(idx, 'role')" @click="selectRole(r)"
                                    :class="{'bg-neutral-100': isHighlighted(visibleIndex(idx, 'role'))}"
                                    class="px-3 py-2 cursor-pointer">
                                    <span x-text="r.name"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Selected chips -->
    <div class="mt-3 flex flex-wrap gap-2" aria-live="polite">
        <template x-for="u in selected.users" :key="'selu-'+u.id">
            <span class="inline-flex items-center gap-2 bg-neutral-100 text-neutral-800 px-2 py-1 rounded-full">
                <x-shared::avatar :src="''" class="h-5 w-5" :alt="'User'" x-bind:src="u.avatar_url"></x-shared::avatar>
                <span x-text="u.display_name" class="text-sm"></span>
                <button type="button" class="text-neutral-500 hover:text-neutral-800" @click="removeUser(u.id)" aria-label="{{ __('profile::picker.remove') }}">×</button>
                <input type="hidden" name="target_users[]" x-bind:value="u.id" />
            </span>
        </template>
        <template x-if="!profilesOnly">
            <template x-for="r in selected.roles" :key="'selr-'+r.slug">
                <span class="inline-flex items-center gap-2 bg-accent/10 text-neutral-800 px-2 py-1 rounded-full">
                    <span x-text="r.name" class="text-sm"></span>
                    <button type="button" class="text-neutral-500 hover:text-neutral-800" @click="removeRole(r.slug)" aria-label="{{ __('profile::picker.remove') }}">×</button>
                    <input type="hidden" name="target_roles[]" x-bind:value="r.slug" />
                </span>
            </template>
        </template>
    </div>

    <script>
        function profileRolePicker() {
            return {
                q: '',
                open: false,
                loading: false,
                results: { profiles: [], roles: [] },
                selected: { users: [], roles: [] },
                highlightIndex: -1,
                counts: { profiles: 0, roles: 0 },
                profilesOnly: false,

                init({ initialUserIds = [], initialRoleSlugs = [], profilesOnly = false } = {}) {
                    this.profilesOnly = profilesOnly;
                    // Preload existing selections
                    this.preload(initialUserIds, initialRoleSlugs);
                    this.$watch('q', (value) => {
                        const q = (value || '').trim();
                        if (q.length < 2) { this.results = { profiles: [], roles: [] }; this.open = false; return; }
                        this.fetchResults(q);
                    });
                },
                async preload(userIds, roleSlugs) {
                    if (Array.isArray(userIds) && userIds.length) {
                        try {
                            const res = await fetch(`/profile/lookup/by-ids?ids=${encodeURIComponent(userIds.join(','))}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const data = await res.json();
                            const fresh = (data.profiles || []).filter(p => !this.selected.users.some(u => u.id === p.id));
                            this.selected.users.push(...fresh);
                        } catch (e) { console.error(e); }
                    }
                    if (Array.isArray(roleSlugs) && roleSlugs.length) {
                        try {
                            const res = await fetch(`/auth/roles/by-slugs?slugs=${encodeURIComponent(roleSlugs.join(','))}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const data = await res.json();
                            const fresh = (data.roles || []).filter(r => !this.selected.roles.some(x => x.slug === r.slug));
                            this.selected.roles.push(...fresh);
                        } catch (e) { console.error(e); }
                    }
                },
                openIfAny() { if ((this.results.profiles.length + this.results.roles.length) > 0) this.open = true; },
                close() { this.open = false; this.highlightIndex = -1; },
                async fetchResults(q) {
                    this.loading = true;
                    try {
                        const fetches = [
                            fetch(`/profile/lookup?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                        ];
                        if (!this.profilesOnly) {
                            fetches.push(fetch(`/auth/roles/lookup?q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }));
                        }
                        const responses = await Promise.all(fetches);
                        const pJson = await responses[0].json();
                        const rJson = this.profilesOnly ? { roles: [] } : await responses[1].json();
                        this.results = {
                            profiles: (pJson.profiles || []).filter(p => !this.selected.users.some(u => u.id === p.id)),
                            roles: this.profilesOnly ? [] : (rJson.roles || []).filter(r => !this.selected.roles.some(x => x.slug === r.slug)),
                        };
                        this.counts = { profiles: this.results.profiles.length, roles: this.results.roles.length };
                        this.open = (this.counts.profiles + this.counts.roles) > 0;
                        this.highlightIndex = -1;
                        this.$nextTick(() => { if (window.Alpine && Alpine.initTree) Alpine.initTree(this.$refs.dropdown); });
                    } catch (e) { console.error(e); }
                    finally { this.loading = false; }
                },
                visibleIndex(idx, type) {
                    return type === 'profile' ? idx : (this.results.profiles.length + idx);
                },
                isHighlighted(idx) { return this.highlightIndex === idx; },
                highlightNext() {
                    const total = this.results.profiles.length + this.results.roles.length;
                    if (!total) return;
                    this.highlightIndex = Math.min(total - 1, this.highlightIndex + 1);
                },
                highlightPrev() {
                    const total = this.results.profiles.length + this.results.roles.length;
                    if (!total) return;
                    this.highlightIndex = Math.max(0, this.highlightIndex - 1);
                },
                activateHighlighted() {
                    const totalProfiles = this.results.profiles.length;
                    if (this.highlightIndex < 0) return;
                    if (this.highlightIndex < totalProfiles) {
                        const p = this.results.profiles[this.highlightIndex];
                        if (p) this.selectProfile(p);
                    } else {
                        const r = this.results.roles[this.highlightIndex - totalProfiles];
                        if (r) this.selectRole(r);
                    }
                },
                selectProfile(p) {
                    if (!this.selected.users.some(u => u.id === p.id)) {
                        this.selected.users.push(p);
                    }
                    this.results.profiles = this.results.profiles.filter(x => x.id !== p.id);
                    this.closeIfEmpty();
                },
                selectRole(r) {
                    if (!this.selected.roles.some(x => x.slug === r.slug)) {
                        this.selected.roles.push(r);
                    }
                    this.results.roles = this.results.roles.filter(x => x.slug !== r.slug);
                    this.closeIfEmpty();
                },
                removeUser(id) {
                    this.selected.users = this.selected.users.filter(u => u.id !== id);
                },
                removeRole(slug) {
                    this.selected.roles = this.selected.roles.filter(r => r.slug !== slug);
                },
                closeIfEmpty() {
                    if ((this.results.profiles.length + this.results.roles.length) === 0) this.close();
                }
            }
        }
    </script>
</div>
