<x-app-layout seasonal-background="true">
    <div class="flex flex-col items-center pt-16">
        <div class="surface-secondary text-on-surface w-full sm:max-w-md mt-6 px-6 py-4 overflow-hidden">
            <div class="mb-4 text-sm">
                {{ __('auth::forgot.intro') }}
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
                @csrf

                <!-- Email Address -->
                <div class="flex flex-col gap-2">
                    <x-input-label for="email" class="text-on-surface" :value="__('auth::forgot.email')" />
                    <x-text-input id="email" class="w-full text-fg" type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="error-on-surface" />
                </div>

                <div class="flex items-center justify-end">
                    <x-shared::button color="accent" type="submit">
                        {{ __('auth::forgot.submit') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

