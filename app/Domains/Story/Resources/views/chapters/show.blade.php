@section('title', $vm->seo->title)
@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $vm->seo->title }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $vm->seo->title }}">
    <meta property="og:image" content="{{ $vm->seo->coverImage }}">
    <meta name="twitter:image" content="{{ $vm->seo->coverImage }}">
@endpush

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
                            <div class="flex items-center gap-3">
                                <a href="{{ route('chapters.edit', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                                   class="text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1"
                                   aria-label="{{ __('story::chapters.actions.edit') }}"
                                   title="{{ __('story::chapters.actions.edit') }}">
                                    <span class="material-symbols-outlined">edit</span>
                                </a>
                                <button type="button"
                                        class="text-red-600 hover:text-red-800 inline-flex items-center gap-1"
                                        aria-label="{{ __('story::chapters.actions.delete') }}"
                                        title="{{ __('story::chapters.actions.delete') }}"
                                        x-data
                                        x-on:click="$dispatch('open-modal', 'confirm-delete-chapter')">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">{{ $vm->story->title }}</div>

                    <div class="mt-2 mb-6 text-sm text-gray-700">
                        <x-shared::popover placement="top" width="16rem">
                            <x-slot name="trigger">
                                <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                                    <span class="material-symbols-outlined text-[16px] leading-none">visibility</span>
                                    <span>@compactNumber($vm->readsLogged)</span>
                                </span>
                            </x-slot>
                            <div class="font-semibold text-gray-900">{{ __('story::chapters.reads.label') }}</div>
                            <div class="text-gray-700">{{ __('story::chapters.reads.tooltip') }}</div>
                        </x-shared::popover>
                    </div>

                    @if(!empty($vm->chapter->author_note))
                        <aside class="mb-6 p-4 bg-gray-50 border rounded">
                            <div class="font-medium mb-2">{{ __('story::chapters.author_note') }}</div>
                            <div class="prose max-w-none rich-content">{!! $vm->chapter->author_note !!}</div>
                        </aside>
                    @endif

                    <article class="prose rich-content max-w-none [text-indent:2rem] leading-8">
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
                                @if($vm->chapter->status === \App\Domains\Story\Models\Chapter::STATUS_PUBLISHED && !$vm->isAuthor)
                                    @auth
                                        <button
                                            type="button"
                                            id="markReadToggle"
                                            class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-sm transition-colors {{ $vm->isReadByMe ? 'bg-green-600 text-white hover:bg-green-700 border-green-600' : 'bg-blue-600 text-white hover:bg-blue-700 border-blue-600' }}"
                                            data-read="{{ $vm->isReadByMe ? '1' : '0' }}"
                                            data-url-read="{{ route('chapters.read.mark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                                            data-url-unread="{{ route('chapters.read.unmark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                                            data-label-read="{{ __('story::chapters.actions.marked_read') }}"
                                            data-label-unread="{{ __('story::chapters.actions.mark_as_read') }}"
                                        >
                                            <span class="material-symbols-outlined text-[18px] leading-none" id="markReadIcon">
                                                {{ $vm->isReadByMe ? 'check_circle' : 'radio_button_unchecked' }}
                                            </span>
                                            <span id="markReadLabel">
                                                {{ $vm->isReadByMe ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}
                                            </span>
                                        </button>
                                    @endauth
                                @endif
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

                    <!-- Comments -->
                    <div class="mt-12">
                        <x-comment-list entity-type="chapter" entity-id="{{ $vm->chapter->id }}" page="0" perPage="5" />
                    </div>
                </div>
            </div>
        </div>
    </div>


@push('scripts')
<script>
    (function() {
        let inFlight = false; // simple in-flight guard during network requests

        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function setStateClasses(btn, read) {
            const toBlue = ['bg-blue-600','hover:bg-blue-700','border-blue-600'];
            const toGreen = ['bg-green-600','hover:bg-green-700','border-green-600'];
            const neutralText = 'text-white';
            btn.classList.remove(...toBlue, ...toGreen);
            btn.classList.add(neutralText);
            btn.classList.add(...(read ? toGreen : toBlue));
        }

        async function toggleLoggedRead(toggleBtn) {
            if (inFlight) return;
            inFlight = true;
            toggleBtn.disabled = true;

            const csrf = getCsrf();
            const isRead = toggleBtn.getAttribute('data-read') === '1';
            const url = isRead ? toggleBtn.getAttribute('data-url-unread') : toggleBtn.getAttribute('data-url-read');
            const method = isRead ? 'DELETE' : 'POST';

            const icon = document.getElementById('markReadIcon');
            const label = document.getElementById('markReadLabel');
            const labelRead = toggleBtn.getAttribute('data-label-read') || 'Read';
            const labelUnread = toggleBtn.getAttribute('data-label-unread') || 'Mark as read';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'text/plain',
                    },
                });
                if (res.status === 204) {
                    const nowRead = !isRead;
                    icon.textContent = nowRead ? 'check_circle' : 'radio_button_unchecked';
                    label.textContent = nowRead ? labelRead : labelUnread;
                    toggleBtn.setAttribute('data-read', nowRead ? '1' : '0');
                    setStateClasses(toggleBtn, nowRead);
                }
            } catch {}
            toggleBtn.disabled = false;
            inFlight = false;
        }

        function bindLoggedToggle() {
            const toggleBtn = document.getElementById('markReadToggle');
            if (!toggleBtn) return;
            toggleBtn.addEventListener('click', () => toggleLoggedRead(toggleBtn));
        }

        // init
        bindLoggedToggle();
    })();
</script>
@endpush

@if(auth()->check() && $vm->story->isAuthor((int)auth()->id()))
    <x-shared::confirm-modal
        name="confirm-delete-chapter"
        :title="__('story::chapters.actions.delete')"
        :body="__('story::show.chapter.confirm_delete_warning')"
        :cancel="__('story::show.cancel')"
        :confirm="__('story::show.confirm_delete')"
        :action="route('chapters.destroy', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug])"
        method="DELETE"
        maxWidth="md"
    />
@endif
</x-app-layout>