<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('story::create.title') }}
        </h2>
    </x-slot>

    <div class="bg-transparent">
        <div class="p-6 text-gray-900">
            <div class="max-w-3xl mx-auto">
                <form action="{{ route('stories.store') }}" method="POST" novalidate>
                    @csrf

                    <x-story::form :referentials="$referentials" />

                    <div class="text-sm">{{ __('story::create.hint')}}</div>

                    <div class="mt-6 flex justify-center">
                        <x-shared::button color="accent" type="submit">
                            {{ __('story::create.actions.continue') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>