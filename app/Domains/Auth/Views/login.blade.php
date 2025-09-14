<x-app-layout seasonal-background="true">
    <div class="h-full flex flex-col items-center pt-16">
        <div class="surface-secondary w-full sm:max-w-md mt-6 px-6 py-4 shadow-md overflow-hidden sm:rounded-lg">
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('auth::login.email')" class="text-on-surface" />
                    <x-text-input id="email" class="mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 error-on-surface" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('auth::login.password')" class="text-on-surface" />

                    <x-text-input id="password" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="current-password" />

                    <x-input-error :messages="$errors->get('password')" class="mt-2 error-on-surface" />
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center text-on-surface">
                        <input id="remember_me" type="checkbox" class="rounded border-accent/90 text-on-surface/90 shadow-sm focus:ring-accent" name="remember">
                        <span class="ms-2 text-sm">{{ __('auth::login.remember') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    @if (Route::has('password.request'))
                    <a class="text-on-surface underline text-sm hover:text-on-surface/90 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('auth::login.forgot') }}
                    </a>
                    @endif

                    <x-shared::button class="ms-3" color="accent" type="submit">
                        {{ __('auth::login.submit') }}
                    </x-shared::button>
                </div>
            </form>

            <!-- Account creation CTA -->
            <p class="mt-6 text-sm text-on-surface text-center">
                {{ __('auth::login.no_account') }}
                <a href="{{ route('register') }}" class="hover:text-on-surface/90 underline font-medium">
                    {{ __('auth::login.create_one') }}
                </a>
            </p>
        </div>
    </div>
</x-app-layout>