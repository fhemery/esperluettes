@section('title', $vm->seo->title)
@push('meta')
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $vm->seo->title }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $vm->seo->title }}">
<meta property="og:image" content="{{ $vm->seo->coverImage }}">
<meta name="twitter:image" content="{{ $vm->seo->coverImage }}">
@endpush

<x-app-layout :display-ribbon="true">
    <div class="w-full grid md:grid-cols-[230px_1fr] lg:grid-cols-[230px_1fr_230px] gap-4">
        <aside class="hidden md:block">
            <x-story::chapter.story-nav :story="$vm->story" :current-chapter-slug="$vm->chapter->slug" />
        </aside>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-8">
                <div class="flex flex-col items-center gap-2 max-w-[730px]">
                    <h1 class="font-semibold text-3xl flex items-center gap-2 uppercase text-accent text-center">
                        {{ $vm->chapter->title }}
                        @if(!$vm->chapter->isPublished)
                        <span class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 border border-yellow-200">
                            {{ trans('story::chapters.list.not_published') }}
                        </span>
                        @endif

                    </h1>

                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <x-shared::metric-badge
                            icon="visibility"
                            :value="$vm->readsLogged"
                            :label="__('story::chapters.reads.label')"
                            :tooltip="__('story::chapters.reads.tooltip')" />

                        <x-story::words-metric-badge
                            class="ml-2"
                            :nb-words="$vm->wordCount"
                            :nb-characters="$vm->characterCount" />
                    </div>
                    @if($vm->isAuthor)
                    <div class="flex items-center gap-3">
                        <a href="{{ route('chapters.edit', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                            class="text-accent hover:text-accent/80 inline-flex items-center gap-1"
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
                    @else
                    <div>
                        {{__('story::chapter.by')}} <x-profile::inline-names :profiles="$vm->authors" />
                    </div>
                    @endif
                </div>

                @if(!empty($vm->chapter->authorNote))
                <aside class="flex flex-col gap-4">
                    <div class="prose rich-content">
                        <p class="text-accent">{{ __('story::chapter.author_note') }}</p>
                        <!-- This always contains a <p> tag, so it will go to the line anyway -->
                        {!! $vm->chapter->authorNote !!}
                    </div>
                    <div class="flex items-center text-accent">
                        <div class="flex-1 border-t border-4 border-accent"></div>
                        <div class="px-3 text-4xl font-semibold leading-none select-none">&amp;</div>
                        <div class="flex-1 border-t border-4 border-accent"></div>
                    </div>
                </aside>
                @endif

                <article class="prose rich-content max-w-none [text-indent:2rem]">
                    {!! $vm->chapter->content !!}
                </article>


            </div>

            <div class="mt-8 pt-4 border-t border-accent">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        @php($prevButtonClass = $vm->prevChapter ? 'text-tertiary hover:text-tertiary/80' : 'text-tertiary/30 cursor-not-allowed')
                        <a href="{{ $vm->prevChapter ? route('chapters.show', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->prevChapter->slug]) : 'javascript:void(0);' }}"
                            class="flex gap-4 items-center text-lg font-bold {{ $prevButtonClass }}"
                            aria-label="{{ __('story::chapters.navigation.previous') }}">
                            <div class="text-read text-6xl {{ $vm->prevChapter ? 'bg-tertiary hover:bg-tertiary/80' : 'bg-tertiary/30' }} rounded-full w-10 h-10 flex items-center justify-center leading-none ">
                                <span class="transform -translate-y-[4px]">&lt;</span>
                            </div>
                            <div class="max-w-[120px]">{{ __('story::chapters.navigation.previous') }}</div>
                        </a>
                    </div>
                    <div class="text-sm">
                        @if($vm->chapter->isPublished && !$vm->isAuthor)
                        @auth
                        <x-shared::button
                            type="button"
                            id="markReadToggle"
                            :color="$vm->isReadByMe ? 'success' : 'tertiary'"
                            data-read="{{ $vm->isReadByMe ? '1' : '0' }}"
                            data-url-read="{{ route('chapters.read.mark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                            data-url-unread="{{ route('chapters.read.unmark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}"
                            data-label-read="{{ __('story::chapters.actions.marked_read') }}"
                            data-label-unread="{{ __('story::chapters.actions.mark_as_read') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none" id="markReadIcon">
                                {{ $vm->isReadByMe ? 'check_circle' : 'radio_button_unchecked' }}
                            </span>
                            <span id="markReadLabel">
                                {{ $vm->isReadByMe ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}
                            </span>
                        </x-shared::button>
                        @endauth
                        @endif
                    </div>
                    <div>
                        @php($nextButtonClass = $vm->nextChapter ? 'text-tertiary hover:text-tertiary/80' : 'text-tertiary/30 cursor-not-allowed')
                        <a href="{{ $vm->nextChapter ? route('chapters.show', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->nextChapter->slug]) : 'javascript:void(0);' }}"
                            class="flex gap-4 items-center text-xl font-bold {{ $nextButtonClass }}"
                            aria-label="{{ __('story::chapters.navigation.next') }}">
                            <div class="text-right max-w-[120px]">{{ __('story::chapters.navigation.next') }}</div>
                            <div class="text-read text-6xl {{ $vm->nextChapter ? 'bg-tertiary hover:bg-tertiary/80' : 'bg-tertiary/30' }} rounded-full w-10 h-10 flex items-center justify-center leading-none ">
                                <span class="transform -translate-y-[4px]">&gt;</span>
                            </div>
                        </a>
                    </div>

                </div>
            </div>

            <!-- Comments -->
            <div class="mt-12">
                <x-comment-list entity-type="chapter" entity-id="{{ $vm->chapter->id }}" page="0" perPage="5" />
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
                const toBlue = ['bg-blue-600', 'hover:bg-blue-700', 'border-blue-600'];
                const toGreen = ['bg-green-600', 'hover:bg-green-700', 'border-green-600'];
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

    @if($vm->isAuthor)
    <x-shared::confirm-modal
        name="confirm-delete-chapter"
        :title="__('story::chapters.actions.delete')"
        :body="__('story::show.chapter.confirm_delete_warning')"
        :cancel="__('story::show.cancel')"
        :confirm="__('story::show.confirm_delete')"
        :action="route('chapters.destroy', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug])"
        method="DELETE"
        maxWidth="md" />
    @endif
</x-app-layout>