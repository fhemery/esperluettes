@section('title', $story->title . ' — ' . $chapter->title . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ url('/stories/'.$story->slug) }}" class="text-indigo-600 hover:text-indigo-800">&larr; {{ __('story::chapters.back_to_story') }}</a>
                    </div>

                    <div class="flex items-start justify-between mb-2">
                        <h1 class="font-semibold text-2xl flex items-center gap-2">
                            {{ $chapter->title }}
                            @if($chapter->status !== \App\Domains\Story\Models\Chapter::STATUS_PUBLISHED)
                                <span class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 border border-yellow-200">
                                    {{ trans('story::chapters.list.draft') }}
                                </span>
                            @endif
                        </h1>

                        @if(auth()->check() && $story->isAuthor((int)auth()->id()))
                            <a href="{{ route('chapters.edit', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]) }}"
                               class="text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1"
                               aria-label="{{ __('story::chapters.actions.edit') }}"
                               title="{{ __('story::chapters.actions.edit') }}">
                                <span class="material-symbols-outlined">edit</span>
                            </a>
                        @endif
                    </div>
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
