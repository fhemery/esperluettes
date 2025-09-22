@section('title', __('story::chapters.edit.title', ['title' => $chapter->title]) . ' – ' . $story->title)

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-2">
                        <x-shared::button color="neutral" onclick="window.history.back()">
                            {{ __('shared::actions.back') }}
                        </x-shared::button>
                    </div>
                    <h1 class="font-semibold text-2xl mb-6">{{ __('story::chapters.edit.heading', ['story' => $story->title]) }}</h1>

                    <form method="POST" action="{{ route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]) }}">
                        @csrf
                        @method('PUT')

                        @include('story::chapters.partials.form')

                        <div class="mt-8 flex justify-end gap-12">
                            <x-shared::button color="accent" type="submit">{{ __('story::chapters.form.update') }}</x-shared::button>
                            <a href="{{ url('/stories/'.$story->slug.'/chapters/'.$chapter->slug) }}">
                                <x-shared::button color="neutral">{{ __('story::chapters.form.cancel') }}</x-shared::button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
