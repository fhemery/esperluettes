<x-app-layout>
    <div class="max-w-4xl w-full mx-auto flex-1 text-fg overflow-hidden flex flex-col gap-4">
        <h2 class="font-semibold text-xl text-accent">
            {{ __('story::create.title') }}
        </h2>

        <div class="bg-transparent flex-1 flex flex-col gap-4">
            <form action="{{ route('stories.store') }}" method="POST" novalidate
                class="flex-1 flex flex-col gap-4">
                @csrf

                <x-story::form :referentials="$referentials" />

                <div class="text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined">info</span>
                    {{ __('story::create.hint')}}
                </div>

                <div class="flex justify-center gap-4">
                    <x-shared::button color="neutral" type="button" onclick="history.back()">
                        {{ __('story::create.actions.cancel') }}
                    </x-shared::button>
                    <x-shared::button color="accent" type="submit">
                        {{ __('story::create.actions.create') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
</x-app-layout>