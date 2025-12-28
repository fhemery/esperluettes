@section('title', __('settings::settings.page_title'))
<x-app-layout size="lg">
    <x-shared::title icon="settings">{{ __('settings::settings.page_title') }}</x-shared::title>

    @if(empty($tabs))
        <div class="surface-read text-on-surface p-8 text-center mt-6">
            <p class="text-fg/50">{{ __('settings::settings.no_settings') }}</p>
        </div>
    @else
        <div class="mt-6 flex flex-col md:flex-row gap-6" x-data="settingsPage('{{ $activeTab?->id }}')">
            {{-- Tabs navigation --}}
            {{-- Mobile: horizontal scrollable tabs --}}
            <div class="md:hidden overflow-x-auto">
                <div class="flex gap-2 pb-2">
                    @foreach($tabs as $tab)
                        <button
                            type="button"
                            @click="switchTab('{{ $tab->id }}')"
                            :class="activeTab === '{{ $tab->id }}' ? 'bg-primary text-on-primary' : 'bg-surface-alt text-fg hover:bg-surface-alt/80'"
                            class="px-4 py-2 rounded-lg whitespace-nowrap flex items-center gap-2 transition-colors"
                        >
                            @if($tab->icon)
                                <span class="material-symbols-outlined text-sm">{{ $tab->icon }}</span>
                            @endif
                            <span>{{ __($tab->nameKey) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Desktop: vertical tabs sidebar --}}
            <div class="hidden md:block w-64 shrink-0">
                <div class="surface-read rounded-lg overflow-hidden">
                    @foreach($tabs as $tab)
                        <button
                            type="button"
                            @click="switchTab('{{ $tab->id }}')"
                            :class="activeTab === '{{ $tab->id }}' ? 'bg-primary text-on-primary' : 'text-fg hover:bg-surface-alt'"
                            class="w-full px-4 py-3 text-left flex items-center gap-3 transition-colors"
                        >
                            @if($tab->icon)
                                <span class="material-symbols-outlined">{{ $tab->icon }}</span>
                            @endif
                            <span>{{ __($tab->nameKey) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Tab content area --}}
            <div class="flex-1 min-w-0">
                <div id="settings-content" class="surface-read text-on-surface rounded-lg">
                    {{-- Initial content loaded server-side --}}
                    @include('settings::partials.tab-content', ['tab' => $activeTab, 'sections' => $sections])
                </div>

                {{-- Loading indicator --}}
                <div x-show="loading" x-cloak class="surface-read text-on-surface rounded-lg p-8 text-center">
                    <span class="material-symbols-outlined animate-spin text-4xl text-primary">progress_activity</span>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        function settingsPage(initialTab) {
            return {
                activeTab: initialTab,
                loading: false,

                async switchTab(tabId) {
                    if (this.activeTab === tabId) return;

                    this.loading = true;
                    this.activeTab = tabId;

                    try {
                        const response = await fetch(`/settings/${tabId}`, {
                            headers: {
                                'Accept': 'text/html',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) throw new Error('Failed to load tab');

                        const html = await response.text();
                        document.getElementById('settings-content').innerHTML = html;

                        // Update URL without reload
                        history.pushState({}, '', `/settings?tab=${tabId}`);
                    } catch (e) {
                        console.error('Failed to load tab:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
