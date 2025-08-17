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
                            <div class="mb-5">
                                <x-input-label for="title" :value="__('story::create.form.title.label')" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="{{ __('story::create.form.title.placeholder') }}" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <x-input-label for="description" :value="__('story::shared.description.label')" />
                                <x-shared::editor
                                    id="story-description-editor"
                                    name="description"
                                    :max="3000"
                                    :nbLines="15"
                                    class="mt-1 block w-full"
                                />
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="mb-6">
                                <x-input-label for="visibility" :value="__('story::shared.visibility.label')" />
                                <div class="flex items-center gap-2">
                                    <select id="visibility" name="visibility" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="public">{{ __('story::shared.visibility.options.public') }}</option>
                                        <option value="community">{{ __('story::shared.visibility.options.community') }}</option>
                                        <option value="private">{{ __('story::shared.visibility.options.private') }}</option>
                                    </select>
                                    <x-shared::help>
                                        <div>
                                            {{ __('story::shared.visibility.help.intro') }}
                                            <ul>
                                                <li>{{ __('story::shared.visibility.help.public') }}</li>
                                                <li>{{ __('story::shared.visibility.help.community') }}</li>
                                                <li>{{ __('story::shared.visibility.help.private') }}</li>
                                                
                                            </ul>
                                        </div>
                                    </x-shared::help>
                                </div>
                                <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                            </div>

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
