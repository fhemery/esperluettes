<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('story::create.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-gray-600 mb-6">{{ __('story::create.intro') }}</p>

                    <div class="max-w-3xl">
                        <form action="{{ route('stories.store') }}" method="POST" novalidate>
                            @csrf

                            <x-story::form :types="$types" />

                            <x-primary-button type="submit">
                                {{ __('story::create.actions.continue') }}
                            </x-primary-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
