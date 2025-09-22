<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('story::create.title') }}
        </h2>
    </x-slot>

    <div class="bg-transparent">
        <div class="text-gray-900">
            <div class="max-w-3xl mx-auto">
                <form action="{{ route('stories.store') }}" method="POST" novalidate>
                    @csrf

                    <x-story::form :referentials="$referentials" />

                    <div class="text-sm">{{ __('story::create.hint')}}</div>

                    <div class="mt-6 flex justify-center gap-12">
                        <x-shared::button color="accent" type="submit">
                            {{ __('story::create.actions.create') }}
                        </x-shared::button>
                        <x-shared::button color="neutral" type="button" onclick="history.back()">
                            {{ __('story::create.actions.cancel') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>