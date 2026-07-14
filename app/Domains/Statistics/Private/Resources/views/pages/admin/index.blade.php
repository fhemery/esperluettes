<x-admin::layout>
    <x-slot name="title">{{ __('statistics::admin.title') }}</x-slot>

    <div class="flex flex-col gap-8 max-w-4xl">
        <h1 class="text-2xl font-bold">{{ __('statistics::admin.title') }}</h1>

        <x-statistics::stat-widget
            statistic-key="global.total_users"
            :label="__('statistics::admin.users')"
        />
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
