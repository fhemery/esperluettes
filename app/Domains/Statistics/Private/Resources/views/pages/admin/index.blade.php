<x-admin::layout>
    <x-slot name="title">{{ __('statistics::admin.title') }}</x-slot>

    <div class="flex flex-col gap-8">
        <h1 class="text-2xl font-bold">{{ __('statistics::admin.title') }}</h1>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
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
            <x-statistics::comment-summary
                :label="__('statistics::admin.comments')"
            />
        </div>

        <x-shared::tabs
            :tabs="[
                ['key' => 'users', 'label' => __('statistics::admin.tab_users')],
                ['key' => 'content', 'label' => __('statistics::admin.tab_content')],
                ['key' => 'comments', 'label' => __('statistics::admin.tab_comments')],
            ]"
            initial="users"
            color="primary"
        >
            <div x-show="tab === 'users'" x-cloak class="flex flex-col gap-6 pt-6 max-w-4xl" role="tabpanel" id="tabs-panel-users" aria-labelledby="tabs-tab-users">
                <x-statistics::stat-widget
                    statistic-key="global.total_users"
                    :label="__('statistics::admin.users')"
                />
            </div>

            <div x-show="tab === 'content'" x-cloak class="flex flex-col gap-6 pt-6 max-w-4xl" role="tabpanel" id="tabs-panel-content" aria-labelledby="tabs-tab-content">
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

            <div x-show="tab === 'comments'" x-cloak class="flex flex-col gap-6 pt-6 max-w-4xl" role="tabpanel" id="tabs-panel-comments" aria-labelledby="tabs-tab-comments">
                <x-statistics::stat-widget
                    statistic-key="global.total_comments"
                    :label="__('statistics::admin.comments')"
                />
                <x-statistics::comment-breakdown-chart
                    :root-label="__('statistics::admin.root_comments')"
                    :reply-label="__('statistics::admin.reply_comments')"
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
