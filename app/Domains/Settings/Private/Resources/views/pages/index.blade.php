@section('title', __('settings::settings.page_title'))
<x-app-layout size="lg">
    <x-shared::title icon="settings">{{ __('settings::settings.page_title') }}</x-shared::title>

    @if(empty($tabs))
        <div class="surface-read text-on-surface p-8 text-center mt-6">
            <p class="text-fg/50">{{ __('settings::settings.no_settings') }}</p>
        </div>
    @else
        @php
            $tabsArray = collect($tabs)->map(fn($tab) => [
                'key' => $tab->id,
                'label' => __($tab->nameKey),
                'icon' => $tab->icon,
            ])->toArray();
        @endphp

        <div class="mt-6" x-data="settingsPage('{{ $activeTab?->id }}')">
            {{-- Scrollable tabs navigation --}}
            <x-shared::scrollable-tabs 
                :tabs="$tabsArray" 
                :active-tab="$activeTab?->id" 
                mode="button"
                on-tab-click="switchTab"
            />

            {{-- Tab content area --}}
            <div class="mt-4">
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
