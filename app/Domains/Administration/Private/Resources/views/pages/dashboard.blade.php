<x-admin::layout>
    <div class="flex flex-col gap-8 surface-read text-on-surface p-4">
        <x-shared::title>{{ __('administration::dashboard.title') }}</x-shared::title>
        
        <div class="surface-bg text-on-surface p-6">
            <p class="text-lg">{{ __('administration::dashboard.welcome') }}</p>
            <p class="mt-4">{{ __('administration::dashboard.description') }}</p>
        </div>
    </div>
</x-admin::layout>
