<x-app-layout size="md">
    <div class="w-full flex-1 overflow-hidden flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-4">
        <x-shared::title>{{ __('story::create.title') }}</x-shared::title>

        <form action="{{ route('stories.store') }}" method="POST" novalidate
            class="bg-transparent flex-1 flex flex-col gap-4">
            @csrf

            <x-story::form :referentials="$referentials" />

            <div class="text-sm flex items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                {{ __('story::create.hint')}}
            </div>

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" :outline="true" type="button" onclick="history.back()">
                    {{ __('story::create.actions.cancel') }}
                </x-shared::button>
                <x-shared::button color="accent" type="submit">
                    {{ __('story::create.actions.create') }}
                </x-shared::button>
            </div>
        </form>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
</x-app-layout>