<x-app-layout seasonal-background="true">
    <div
        class="h-full flex flex-col py-8
    surface-secondary gap-4 w-full sm:max-w-2xl mx-auto px-6 py-4 overflow-hidden ">
        <x-shared::title class="text-white">{{ __('auth::shared.accept_terms.title') }}</x-shared::title>
        <form method="POST" action="{{ route('compliance.terms.accept') }}">
            @csrf

            <div class="block mb-4">
                <label for="accept_terms" class="inline-flex items-center text-on-surface">
                    <input id="accept_terms" type="checkbox"
                        class="rounded border-accent/90 text-on-surface/90 shadow-sm focus:ring-accent"
                        name="accept_terms" value="1" required>
                    <span class="ms-2 text-sm">{!! __('auth::shared.accept_terms.label') !!}</span>
                </label>
                <x-input-error :messages="$errors->get('accept_terms')" class="mt-2 error-on-surface" />
            </div>

            <div class="flex items-center justify-center">

                <x-shared::button color="accent" type="submit">
                    {{ __('auth::shared.accept_terms.submit') }}
                </x-shared::button>
            </div>
        </form>
        <div class="flex justify-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-on-surface underline text-sm hover:text-on-surface/90">
                    {{ __('auth::shared.accept_terms.logout') }}
                </button>
            </form>
        </div>

    </div>
</x-app-layout>
