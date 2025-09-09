<x-shared::app-layout>
    <div class="h-full flex items-center">
        <div class="max-w-2xl mx-auto px-4 w-full">
            <div class="bg-white shadow sm:rounded-lg p-8 text-center">
                <h1 class="text-3xl font-semibold text-gray-900 mb-3">{{ __('shared::errors.404.title') }}</h1>
                <p class="text-gray-600 mb-8">{{ __('shared::errors.404.description') }}</p>
                @guest
                <p class="text-gray-600 mb-8">{{ __('shared::errors.404.guest_additional_description') }}</p>
                @endguest

                <div class="flex items-center justify-center gap-3">
                    <x-shared::button onclick="history.back()" color="neutral">
                        {{ __('shared::errors.actions.back') }}
                    </x-shared::button>
                    @auth
                        <a href="{{ route('dashboard') }}">
                            {{ __('shared::errors.actions.go_to_dashboard') }}
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

            <div class="flex items-center justify-center w-full">
                <img src="{{ asset('images/errors/404.png') }}" alt="404" class="w-full">
            </div>
        </div>
    </div>
    
</x-shared::app-layout>
