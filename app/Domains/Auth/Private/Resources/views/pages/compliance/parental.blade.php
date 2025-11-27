<x-app-layout seasonal-background="true">
    <div class="h-full flex flex-col items-center py-8">
        <div class="surface-secondary text-on-surface flex flex-col gap-6 w-full sm:max-w-2xl my-4 px-6 py-4">
            <x-shared::title class="text-on-surface">{{ __('auth::compliance.title') }}</x-shared::title>
            <div>{!! __('auth::compliance.description') !!}</div>

            <div> {{ __('auth::compliance.instructions_first_line') }} </div>
            <a href="{{ asset('documents/autorisation_legale_esperluettes.pdf') }}" class="flex items-center space-x-2">
                <x-shared::button color="accent" type="button" icon="file_save">
                    {{ __('auth::compliance.download_button_text') }}
                </x-shared::button>
            </a>

            <div> {{ __('auth::compliance.instructions_second_line') }} </div>

            <form method="POST" action="{{ route('compliance.parental.upload') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <input id="parental_authorization" type="file" name="parental_authorization"
                        accept=".pdf,.jpg,.jpeg,.png" required
                        class="mt-1 block w-full text-sm text-on-surface
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-accent file:text-white
                                  hover:file:bg-accent/90
                                  cursor-pointer">
                    <x-input-error :messages="$errors->get('parental_authorization')" class="mt-2 error-on-surface" />
                </div>

                <div class="flex items-center justify-between">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-on-surface underline text-sm hover:text-on-surface/90">
                            {{ __('auth::compliance.logout_button_text') }}
                        </button>
                    </form>

                    <x-shared::button color="accent" type="submit">
                        {{ __('auth::compliance.upload_button_text') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
