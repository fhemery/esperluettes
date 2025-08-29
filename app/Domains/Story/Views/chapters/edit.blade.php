@section('title', __('story::chapters.edit.title') . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="font-semibold text-2xl mb-6">{{ __('story::chapters.edit.heading', ['story' => $story->title]) }}</h1>

                    <form method="POST" action="{{ route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]) }}">
                        @csrf
                        @method('PUT')

                        @include('story::chapters.components.form')

                        <div class="mt-8 flex justify-end gap-3">
                            <a href="{{ url('/stories/'.$story->slug.'/chapters/'.$chapter->slug) }}" class="px-4 py-2 rounded-md border text-gray-700 hover:bg-gray-50">{{ __('story::chapters.form.cancel') }}</a>
                            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">{{ __('story::chapters.form.update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
