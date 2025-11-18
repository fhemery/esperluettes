<x-admin::layout>
    <x-shared::title>{{ __('administration::maintenance.title') }}</x-shared::title>
    <x-shared::title tag="h2">{{ __('administration::maintenance.empty-cache.title') }}</x-shared::title>
    <div class="flex gap-4 items-center">
        <div> {{ __('administration::maintenance.empty-cache.description') }}</div>
        <div>
            <form method="POST" action="{{ route('administration.maintenance.empty-cache') }}">
                @csrf
                <x-shared::button type="submit" color="primary" icon="bolt">{{ __('administration::maintenance.empty-cache.button-label') }}</x-shared::button>
            </form>
        </div>
    </div>
</x-admin::layout>