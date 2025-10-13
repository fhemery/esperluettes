<section class="space-y-6">
    <header class="flex flex-col gap-2">
        <x-shared::title icon="delete" tag="h2">
            {{ __('auth::account.delete.title') }}
        </x-shared::title>

        <p >
            {{ __('auth::account.delete.description') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('auth::account.delete.title') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('account.destroy') }}" class="p-6 flex flex-col gap-4">
            @csrf
            @method('delete')

            <x-shared::title tag="h2">
                {{ __('auth::account.delete.confirm_title') }}
            </x-shared::title>

            <p class="mt-1 text-sm">
                {{ __('auth::account.delete.confirm_description') }}
            </p>

            <div class="flex flex-col gap-2">
                <x-input-label for="password" value="{{ __('auth::account.password.current') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('auth::account.password.current') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-4">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('auth::account.delete.submit') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

