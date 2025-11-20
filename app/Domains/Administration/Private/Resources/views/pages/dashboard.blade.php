<x-admin::layout>
    <div class="flex flex-col gap-8 surface-read text-on-surface p-4">
        <x-shared::title>{{ __('administration::dashboard.title') }}</x-shared::title>
        
        <div class="flex flex-col gap-4 surface-bg text-on-surface p-6">
            <p class="text-lg">{{ __('administration::dashboard.welcome') }}</p>
            <p class="">{{ __('administration::dashboard.description') }}</p>
            <p class="flex items-center gap-2">
                <span class="material-symbols-outlined text-warning">info</span>
                {{ __('administration::dashboard.temporary_v2') }}
            </p>
        </div>
    </div>
</x-admin::layout>
