@section('title', $vm->story->title . ' — ' . $vm->chapter->title . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ url('/stories/'.$vm->story->slug) }}" class="text-indigo-600 hover:text-indigo-800">&larr; {{ __('story::chapters.back_to_story') }}</a>
                    </div>

                    <div class="flex items-start justify-between mb-2">
                        <h1 class="font-semibold text-2xl flex items-center gap-2">
                            {{ $vm->chapter->title }}
                            @if($vm->chapter->status !== \App\Domains\Story\Models\Chapter::STATUS_PUBLISHED)
                                <span class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 border border-yellow-200">
                                    {{ trans('story::chapters.list.draft') }}
                                </span>
                            @endif
                        </h1>

                        @if(auth()->check() && $vm->story->isAuthor((int)auth()->id()))
                            <a href="{{ route('chapters.edit', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                               class="text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1"
                               aria-label="{{ __('story::chapters.actions.edit') }}"
                               title="{{ __('story::chapters.actions.edit') }}">
                                <span class="material-symbols-outlined">edit</span>
                            </a>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 mb-6">{{ $vm->story->title }}</div>

                    @if(!empty($vm->chapter->author_note))
                        <aside class="mb-6 p-4 bg-gray-50 border rounded">
                            <div class="font-medium mb-2">{{ __('story::chapters.author_note') }}</div>
                            <div class="prose max-w-none">{!! $vm->chapter->author_note !!}</div>
                        </aside>
                    @endif

                    <article class="prose max-w-none">
                        {!! $vm->chapter->content !!}
                    </article>

                    <div class="mt-8 pt-4 border-t">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                @if($vm->prevChapter)
                                    <a href="{{ route('chapters.show', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->prevChapter->slug]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200"
                                       aria-label="{{ __('story::chapters.navigation.previous') }}">
                                        <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
                                        {{ __('story::chapters.navigation.previous') }}
                                    </a>
                                @else
                                    <button class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-gray-50 text-gray-400 cursor-not-allowed" disabled>
                                        <span class="material-symbols-outlined text-[18px] leading-none">arrow_back</span>
                                        {{ __('story::chapters.navigation.previous') }}
                                    </button>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ __('story::chapters.navigation.mark_read') }}
                            </div>
                            <div>
                                @if($vm->nextChapter)
                                    <a href="{{ route('chapters.show', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->nextChapter->slug]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-gray-100 text-gray-800 hover:bg-gray-200"
                                       aria-label="{{ __('story::chapters.navigation.next') }}">
                                        {{ __('story::chapters.navigation.next') }}
                                        <span class="material-symbols-outlined text-[18px] leading-none">arrow_forward</span>
                                    </a>
                                @else
                                    <button class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-gray-50 text-gray-400 cursor-not-allowed" disabled>
                                        {{ __('story::chapters.navigation.next') }}
                                        <span class="material-symbols-outlined text-[18px] leading-none">arrow_forward</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
