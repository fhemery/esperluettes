@section('title', $story->title . ' — ' . $chapter->title . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ url('/stories/'.$story->slug) }}" class="text-indigo-600 hover:text-indigo-800">&larr; {{ __('story::chapters.back_to_story') }}</a>
                    </div>

                    <h1 class="font-semibold text-2xl mb-2">{{ $chapter->title }}</h1>
                    <div class="text-sm text-gray-600 mb-6">{{ $story->title }}</div>

                    @if(!empty($chapter->author_note))
                        <aside class="mb-6 p-4 bg-gray-50 border rounded">
                            <div class="font-medium mb-2">{{ __('story::chapters.author_note') }}</div>
                            <div class="prose max-w-none">{!! $chapter->author_note !!}</div>
                        </aside>
                    @endif

                    <article class="prose max-w-none">
                        {!! $chapter->content !!}
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
