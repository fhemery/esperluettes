<x-app-layout>
    <div class="bg-seasonal h-full flex flex-col items-center pt-16">
        <div class="bg-dark/90 w-full sm:max-w-md mt-6 px-6 py-4 shadow-md overflow-hidden sm:rounded-lg text-white">
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('auth::login.email')" class="text-white" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('auth::login.password')" class="text-white" />

                    <x-text-input id="password" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="current-password" />

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center text-white">
                        <input id="remember_me" type="checkbox" class="rounded border-accent-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ms-2 text-sm text-white">{{ __('auth::login.remember') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    @if (Route::has('password.request'))
                    <a class="underline text-sm text-white hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('auth::login.forgot') }}
                    </a>
                    @endif

                    <x-shared::button class="ms-3" color="accent">
                        {{ __('auth::login.submit') }}
                    </x-shared::button>
                </div>
            </form>

            <!-- Account creation CTA -->
            <p class="mt-6 text-sm text-white text-center">
                {{ __('auth::login.no_account') }}
                <a href="{{ route('register') }}" class="text-white hover:text-gray-300 hover:underline font-medium">
                    {{ __('auth::login.create_one') }}
                </a>
            </p>
        </div>
    </div>
</x-app-layout>