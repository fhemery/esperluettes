<x-app-layout>
    <div class="bg-seasonal min-h-screen flex flex-col items-center pt-16">
        <div class="bg-dark/90 w-full sm:max-w-md mt-6 px-6 py-4 shadow-md overflow-hidden sm:rounded-lg">
            <form method="POST" action="{{ route('register') }}">
                @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('auth::register.name')" class="text-white" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('auth::register.email')" class="text-white" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('auth::register.password')" class="text-white" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">  
            <x-input-label for="password_confirmation" :value="__('auth::register.confirm_password')" class="text-white" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="['Test1', 'Test2']" class="mt-2 text-white bg-red-600/50 rounded p-4" />
        </div>

        <!-- Activation Code -->
        @if(config('app.require_activation_code', false))
        <div class="mt-4">
            <x-input-label for="activation_code" :value="__('auth::register.activation.label')" class="text-white" />
            <x-text-input id="activation_code" class="block mt-1 w-full" 
                          type="text" 
                          name="activation_code" 
                          :value="old('activation_code')" 
                          required 
                          placeholder="XYZT-ABCDEFGH-IJKL"
                          autocomplete="off" />
            <x-input-error :messages="$errors->get('activation_code')" class="mt-2" />
            <p class="mt-1 text-sm text-gray-600">{{ __('auth::register.activation.help') }}</p>
        </div>
        @endif

        <div class="flex items-center justify-end mt-4 gap-1">
            <a class="underline text-sm text-white hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('auth::register.links.already_registered') }}
            </a>

            <x-shared::button color="accent" class="ms-4" type="submit">
                {{ __('auth::register.submit') }}
            </x-shared::button>
        </div>
            </form>
        </div>
    </div>
</x-app-layout>

