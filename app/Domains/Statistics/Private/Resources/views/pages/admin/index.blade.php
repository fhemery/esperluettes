<x-admin::layout>
    <x-slot name="title">{{ __('statistics::admin.title') }}</x-slot>

    <div class="flex flex-col gap-8">
        <h1 class="text-2xl font-bold">{{ __('statistics::admin.title') }}</h1>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-statistics::stat-summary
                statistic-key="global.total_users"
                :label="__('statistics::admin.users')"
            />
            <x-statistics::stat-summary
                statistic-key="global.total_stories"
                :label="__('statistics::admin.stories')"
            />
            <x-statistics::stat-summary
                statistic-key="global.total_chapters"
                :label="__('statistics::admin.chapters')"
            />
            <x-statistics::stat-summary
                statistic-key="global.total_words"
                :label="__('statistics::admin.words')"
                format="compact"
            />
        </div>

        <x-shared::tabs
            :tabs="[
                ['key' => 'users', 'label' => __('statistics::admin.tab_users')],
                ['key' => 'content', 'label' => __('statistics::admin.tab_content')],
            ]"
            initial="users"
            color="primary"
        >
            <div x-show="tab === 'users'" x-cloak class="flex flex-col gap-6 pt-6 max-w-4xl">
                <x-statistics::stat-widget
                    statistic-key="global.total_users"
                    :label="__('statistics::admin.users')"
                />
            </div>

            <div x-show="tab === 'content'" x-cloak class="flex flex-col gap-6 pt-6 max-w-4xl">
                <x-statistics::stat-widget
                    statistic-key="global.total_stories"
                    :label="__('statistics::admin.stories')"
                />
                <x-statistics::stat-widget
                    statistic-key="global.total_chapters"
                    :label="__('statistics::admin.chapters')"
                />
                <x-statistics::stat-widget
                    statistic-key="global.total_words"
                    :label="__('statistics::admin.words')"
                />
            </div>
        </x-shared::tabs>
    </div>

    @push('scripts')
        @vite('app/Domains/Statistics/Private/Resources/js/charts.js')
        <script>
            window.addEventListener('statistics-charts-ready', () => {
                window.StatisticsCharts?.mountAll();
            });

            if (window.StatisticsCharts) {
                window.StatisticsCharts.mountAll();
            }
        </script>
    @endpush
</x-admin::layout>
