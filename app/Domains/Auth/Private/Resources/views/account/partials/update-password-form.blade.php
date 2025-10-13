<section class="flex flex-col gap-4">
    <header class="flex flex-col gap-2">
        <x-shared::title icon="lock" tag="h2">
            {{ __('auth::account.password.update_title') }}
        </x-shared::title>

        <p>
            {{ __('auth::account.password.update_help') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-2">
        @csrf
        @method('put')

        <div class="flex flex-col gap-2">
            <x-input-label for="update_password_current_password" :value="__('auth::account.password.current')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="update_password_password" :value="__('auth::account.password.new')" />
            <x-text-input id="update_password_password" name="password" type="password" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="flex flex-col gap-2">
            <x-input-label for="update_password_password_confirmation" :value="__('auth::account.password.confirm')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4">
            <x-shared::button color="accent" type="submit">{{ __('Save') }}</x-shared::button>
        </div>
    </form>
</section>
