<x-shared::app-layout>
    <div class="h-full flex items-center">
        <div class="max-w-2xl mx-auto px-4 w-full">
            <div class="bg-white shadow sm:rounded-lg p-8 text-center">
                <x-shared::title>{{ __('shared::errors.419.title') }}</x-shared::title>
                <p class="text-gray-600 mb-8">{{ __('shared::errors.419.description') }}</p>

                <div class="flex items-center justify-center gap-3">
                    <x-shared::button onclick="history.back()" color="neutral">
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
    </div>
</x-shared::app-layout>
