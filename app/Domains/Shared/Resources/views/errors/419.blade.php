<x-shared::app-layout size="sm">
    <div class="w-full flex items-center">
        <div class="surface-read text-on-surface p-8 text-center">
            <x-shared::title>{{ __('shared::errors.419.title') }}</x-shared::title>
            <p class="mb-8">{{ __('shared::errors.419.description') }}</p>

            <div class="flex items-center justify-center gap-3">
                <x-shared::button onclick="history.back()" color="neutral" :outline="true">
                    {{ __('shared::errors.actions.back') }}
                </x-shared::button>
                @auth
                <a href="{{ route('dashboard') }}">
                    <x-shared::button color="primary">
                        {{ __('shared::errors.actions.go_to_dashboard') }}
                    </x-shared::button>
                </a>
                @else
                <a href="{{ route('home') }}">
                    <x-shared::button color="primary">
                        {{ __('shared::errors.actions.back_home') }}
                    </x-shared::button>
                </a>
                @endauth
            </div>
        </div>
    </div>
</x-shared::app-layout>