@section('title', __('story::chapters.create.title') . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="font-semibold text-2xl mb-6">{{ __('story::chapters.create.heading', ['story' => $story->title]) }}</h1>

                    <form method="POST" action="{{ route('chapters.store', ['storySlug' => $story->slug]) }}">
                        @csrf

                        <div class="space-y-6">
                            <!-- Title -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-input-label for="title" :value="__('story::chapters.form.title.label')" />
                                    <span class="text-red-600" aria-hidden="true">*</span>
                                </div>
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                              placeholder="{{ __('story::chapters.form.title.placeholder') }}"
                                              value="{{ old('title', '') }}" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <!-- Author Note -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-input-label for="author_note" :value="__('story::chapters.form.author_note.label')" />
                                    <x-shared::tooltip type="help" :title="__('story::chapters.form.author_note.label')" placement="right">
                                        {{ __('story::chapters.form.author_note.help') }}
                                    </x-shared::tooltip>
                                </div>
                                <x-shared::editor id="chapter-author-note-editor" name="author_note" :nbLines="5" class="mt-1 block w-full" defaultValue="{{ old('author_note', '') }}" />
                                <x-input-error :messages="$errors->get('author_note')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">{{ __('story::chapters.form.author_note.note_limit') }}</p>
                            </div>

                            <!-- Content -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-input-label for="content" :value="__('story::chapters.form.content.label')" />
                                    <span class="text-red-600" aria-hidden="true">*</span>
                                </div>
                                <x-shared::editor id="chapter-content-editor" name="content" :nbLines="20" class="mt-1 block w-full" defaultValue="{{ old('content', '') }}" />
                                <x-input-error :messages="$errors->get('content')" class="mt-2" />
                            </div>

                            <!-- Published toggle (default ON) -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <div class="mt-2">
                                        <x-shared::toggle id="published" :label="__('story::chapters.form.published.label')" name="published" :checked="old('published', '1') ? true : false" value="1" />
                                    </div>
                                    <x-shared::tooltip type="help" :title="__('story::chapters.form.published.help.label')" placement="right">
                                        {{ __('story::chapters.form.published.help.text') }}
                                    </x-shared::tooltip>
                                </div>
                                
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <a href="{{ url('/stories/'.$story->slug) }}" class="px-4 py-2 rounded-md border text-gray-700 hover:bg-gray-50">{{ __('story::chapters.form.cancel') }}</a>
                            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">{{ __('story::chapters.form.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
