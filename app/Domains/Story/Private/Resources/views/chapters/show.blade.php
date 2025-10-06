@section('title', $vm->seo->title)
@push('meta')
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $vm->seo->title }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $vm->seo->title }}">
<meta property="og:image" content="{{ $vm->seo->coverImage }}">
<meta name="twitter:image" content="{{ $vm->seo->coverImage }}">
@endpush

<x-app-layout :display-ribbon="true" :page="$page">
    <div class="w-full grid md:grid-cols-[230px_1fr] lg:grid-cols-[230px_1fr_230px] gap-4">
        <aside class="hidden md:block">
            <x-story::chapter.story-nav :story="$vm->story" :current-chapter-slug="$vm->chapter->slug" />
        </aside>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-8">
                <div class="flex flex-col items-center gap-2 max-w-[730px]">
                    <div class="flex items-center gap-2">
                        <x-shared::title class="text-center uppercase">{{ $vm->chapter->title }}</x-shared::title>
                        @if(!$vm->chapter->isPublished)
                        <x-shared::popover placement="top">
                            <x-slot name="trigger">
                                <span class="material-symbols-outlined text-warning leading-none shrink-0">visibility_off</span>
                            </x-slot>
                            <p>{{ __('story::chapters.list.not_published') }}</p>
                        </x-shared::popover>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 text-sm">

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
                        {{__('story::chapters.by')}} <x-profile::inline-names :profiles="$vm->authors" />
                    </div>
                    @endif
                </div>

                @if(!empty($vm->chapter->authorNote))
                <aside class="flex flex-col gap-4">
                    <div class="prose rich-content text-xl">
                        <p class="text-accent">{{ __('story::chapters.author_note') }}</p>
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

                <article class="prose rich-content max-w-none [text-indent:2rem] text-xl">
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
                            <div class="max-w-[120px] hidden sm:block">{{ __('story::chapters.navigation.previous') }}</div>
                        </a>
                    </div>
                    <div class="text-sm">
                        @if($vm->chapter->isPublished && !$vm->isAuthor)
                        @auth
                        <div
                            x-data="markRead({
                                isRead: {{ $vm->isReadByMe ? 'true' : 'false' }},
                                urlRead: '{{ route('chapters.read.mark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}',
                                urlUnread: '{{ route('chapters.read.unmark', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug]) }}',
                                labelRead: '{{ __('story::chapters.actions.marked_read') }}',
                                labelUnread: '{{ __('story::chapters.actions.mark_as_read') }}',
                                csrf: '{{ csrf_token() }}',
                            })">
                            <x-shared::button x-show="!read" type="button" color="accent" x-on:click="toggle()">
                                <div class="flex items-center gap-2">
                                    <span x-text="labelUnread"></span>
                                </div>
                            </x-shared::button>

                            <x-shared::button x-show="read" type="button" color="success" x-on:click="toggle()">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined leading-none">check_circle</span>
                                    <span x-text="labelRead"></span>
                                </div>
                            </x-shared::button>
                        </div>
                        @endauth
                        @endif
                    </div>
                    <div>
                        @php($nextButtonClass = $vm->nextChapter ? 'text-tertiary hover:text-tertiary/80' : 'text-tertiary/30 cursor-not-allowed')
                        <a href="{{ $vm->nextChapter ? route('chapters.show', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->nextChapter->slug]) : 'javascript:void(0);' }}"
                            class="flex gap-4 items-center text-xl font-bold {{ $nextButtonClass }}"
                            aria-label="{{ __('story::chapters.navigation.next') }}">
                            <div class="text-right max-w-[120px] hidden sm:block">{{ __('story::chapters.navigation.next') }}</div>
                            <div class="text-read text-6xl {{ $vm->nextChapter ? 'bg-tertiary hover:bg-tertiary/80' : 'bg-tertiary/30' }} rounded-full w-10 h-10 flex items-center justify-center leading-none ">
                                <span class="transform -translate-y-[4px]">&gt;</span>
                            </div>
                        </a>
                    </div>

                </div>
            </div>

            <!-- Comments -->
            <div class="mt-12 surface-read text-on-surface p-2 lg:p-6">
                <x-comment-list entity-type="chapter" entity-id="{{ $vm->chapter->id }}" page="0" perPage="5" />
            </div>
        </div>
    </div>


    @push('scripts')
    <script>
        function markRead({
            isRead,
            urlRead,
            urlUnread,
            labelRead,
            labelUnread,
            csrf
        }) {
            return {
                read: isRead,
                labelRead,
                labelUnread,
                _busy: false,
                async toggle() {
                    if (this._busy) return;
                    this._busy = true;
                    const makeRead = !this.read;
                    const url = makeRead ? urlRead : urlUnread;
                    const method = makeRead ? 'POST' : 'DELETE';
                    try {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'text/plain'
                            },
                        });
                        if (res.status === 204) {
                            this.read = makeRead;
                        }
                    } catch (e) {}
                    this._busy = false;
                }
            }
        }
    </script>
    @endpush

    @if($vm->isAuthor)
    <x-shared::confirm-modal
        name="confirm-delete-chapter"
        :title="__('story::chapters.actions.delete')"
        :body="__('story::chapters.confirm_delete_warning', ['chapterTitle' => $vm->chapter->title])"
        :cancel="__('story::show.cancel')"
        :confirm="__('story::show.confirm_delete')"
        :action="route('chapters.destroy', ['storySlug' => $vm->story->slug, 'chapterSlug' => $vm->chapter->slug])"
        method="DELETE"
        maxWidth="md" />
    @endif
</x-app-layout>