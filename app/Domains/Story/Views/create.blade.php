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
                        <form action="#" method="GET" novalidate>
                            <div class="mb-5">
                                <x-input-label for="title" :value="__('story::create.form.title.label')" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="{{ __('story::create.form.title.placeholder') }}" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <x-input-label for="description" :value="__('story::create.form.description.label')" />
                                <textarea id="description" name="description" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('story::create.form.description.placeholder') }}"></textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <x-primary-button type="button">
                                {{ __('story::create.actions.continue') }}
                            </x-primary-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
