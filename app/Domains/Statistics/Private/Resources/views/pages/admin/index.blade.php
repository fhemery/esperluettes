<x-admin::layout>
    <x-slot name="title">{{ __('statistics::statistics.admin.title') }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold mb-8">{{ __('statistics::statistics.admin.title') }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-statistics::stat-card
                statistic-key="global.total_users"
                :label="__('statistics::statistics.admin.users')"
            />
        </div>
        
    </div>

    @push('scripts')
        @vite('app/Domains/Statistics/Private/Resources/js/charts.js')
    @endpush
</x-admin::layout>
