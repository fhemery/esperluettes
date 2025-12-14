<x-shared::app-layout>
    <div class="flex-1 flex flex-col items-center justify-center mx-auto w-full">
        <!-- Panel -->
        <div class="surface-read text-on-surface p-4 text-center">
            <x-shared::title icon="warning">{{ __('shared::errors.500.title') }}</x-shared::title>
            <p class="text-gray-600 mb-4">{{ __('shared::errors.500.description') }}</p>
            <p class="text-gray-600 mb-8">{{ __('shared::errors.500.additional_description') }}</p>

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
                <a href="{{ route('login.with_intended', ['redirect' => request()->fullUrl()]) }}">
                    <x-shared::button color="accent">
                        {{ __('shared::errors.actions.login_to_continue') }}
                    </x-shared::button>
                </a>
                @endauth
            </div>
        </div>

        <div class="flex items-center justify-center">
            <img src="{{ asset("images/themes/{$theme->value}/errors/500.png") }}" alt="500" class="w-full">
        </div>
    </div>

</x-shared::app-layout>
