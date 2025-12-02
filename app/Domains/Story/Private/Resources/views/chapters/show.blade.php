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
    {{-- Mature content gate overlay (not shown to authors of the story) --}}
    @if(!empty($audienceInfo) && $audienceInfo['is_mature'] && !$vm->isAuthor)
        <x-story::mature-content-gate 
            :thresholdAge="$audienceInfo['threshold_age']"
            :storiesUrl="route('stories.index')"
        />
    @endif

    <div
        x-data="chapterPage()"
        x-init="init()"
        @resize.window="handleResize()"
        @if(!empty($audienceInfo) && $audienceInfo['is_mature'] && !$vm->isAuthor) data-mature-content @endif
        class="w-full grid md:grid-cols-[230px_1fr] lg:grid-cols-[230px_1fr_230px] gap-4">
        <aside class="hidden md:block">
            <div id="chapter-nav-host-desktop"></div>
        </aside>

        <!-- Mobile drawer trigger button -->
        <x-shared::badge
            type="button"
            class="md:hidden fixed bottom-6 left-6 z-40"
            color="accent"
            :outline="true"
            aria-controls="mobile-chapter-drawer"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            x-on:click="open = !open"
            title="{{ __('story::chapters.side_nav.label') }}">
            <span class="material-symbols-outlined text-3xl leading-none">side_navigation</span>
        </x-shared::badge>

        <!-- Back to top button. Appears only on scroll top -->
        <x-shared::badge 
            id="inlineScrollTopBtn"
            color="accent" 
            :outline="true" 
            class="fixed bottom-6 right-6 cursor-pointer transition-opacity duration-300 opacity-0 pointer-events-none"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            aria-label="{{ __('story::chapters.back_to_top.label') }}"
        >
            <span class="material-symbols-outlined text-3xl leading-none">
                keyboard_arrow_up
            </span>
        </x-shared::badge>

        <!-- Mobile drawer for chapter navigation -->
        <div class="md:hidden">
            <!-- Edge swipe area (open) -->
            <div class="fixed left-0 top-0 bottom-0 w-3 z-20" aria-hidden="true"
                @touchstart.passive="startX = $event.touches[0].clientX; tracking = true"
                @touchmove.passive="if(tracking && ($event.touches[0].clientX - startX) > 24){ open = true; tracking = false }"
                @touchend.passive="tracking = false"></div>

            <!-- Backdrop -->
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/30 z-30" @click="open = false" aria-hidden="true"></div>

            <!-- Drawer panel (left side) -->
            <div
                id="mobile-chapter-drawer"
                class="fixed top-0 bottom-0 left-0 z-40 w-64 max-w-[85vw] bg-bg border-r border-accent"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="-translate-x-full opacity-0"
                @keydown.escape.window="open = false"
                role="dialog"
                aria-modal="true">
                <div class="p-6 flex items-center justify-between border-b border-accent">
                    <div class="font-medium">{{ $vm->chapter->title }}</div>
                    <button type="button" class="p-1" @click="open = false" aria-label="Close">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="h-full overflow-y-auto p-6">
                    <div id="chapter-nav-host-mobile"></div>
                </div>
            </div>
        </div>

        <!--Chapter navigation and moderation -->
        {{-- This div is not displayed because it is either sent to mobile drawer or desktop dependending on resolution --}}
        <div x-ref="chapterNav" style="display: none">
            <div class="flex flex-col gap-4 items-center">
                <x-story::chapter.story-nav :story="$vm->story" :current-chapter-slug="$vm->chapter->slug" />
                @if($vm->isAuthor)
                <!-- Create chapter button -->
                <x-story::chapter.create-button :storySlug="$vm->story->slug" :canCreateChapter="$canCreateChapter" />
                @endif
                <div class="flex gap-2">
                    @if(!$vm->isAuthor)
                    <div class="flex gap-2">
                        <x-moderation::report-button
                            topic-key="chapter"
                            :entity-id="$vm->chapter->id" />
                        @if($isModerator)
                        <x-moderation::moderation-button
                            badgeColor="warning"
                            position="top"
                            id="chapter-moderator-btn">
                            <x-moderation::action
                                :action="route('chapters.moderation.unpublish', $vm->chapter->slug)"
                                method="POST"
                                :label="__('story::moderation.unpublish.label')" />
                            <x-moderation::action
                                :action="route('chapters.moderation.empty-content', $vm->chapter->slug)"
                                method="POST"
                                :label="__('story::moderation.empty_chapter_content.label')" />
                        </x-moderation::moderation-button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
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
                    <div class="prose rich-content text-lg text-fg/90">
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
                <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-4">
                        <x-shared::title tag="h2" id="comments">{{ __('comment::comments.list.title') }}</x-shared::title>
                        @if($vm->getFeedbackName())
                        <div class="self-end">
                            <x-story::ref-badge
                                name="{{ $vm->getFeedbackName() }}"
                                description="{{ $vm->getFeedbackDescription() }}"
                                color="neutral"
                                :outline="true"
                                :wrap="true"
                                size="sm"
                                icon="forum" />
                                </div>
                        @endif
                </div>

                <x-comment::comment-list-component entity-type="chapter" entity-id="{{ $vm->chapter->id }}" page="0" perPage="5" />
            </div>
        </div>
    </div>



    @push('scripts')
    <script>
        let lastScrollTop = 0;
        const scrollTopBtn = document.getElementById('inlineScrollTopBtn');
        window.addEventListener('scroll', () => {
            const st = window.scrollY || document.documentElement.scrollTop;
            if (st < lastScrollTop) {
                scrollTopBtn?.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                scrollTopBtn?.classList.add('opacity-0', 'pointer-events-none');
            }
            lastScrollTop = st <= 0 ? 0 : st;
        });
    </script>
    <script>
        function chapterPage() {
            return {
                open: false,
                startX: 0,
                tracking: false,
                init() {
                    this.placeNav()
                },
                handleResize() {
                    this.placeNav()
                },
                placeNav() {
                    const desktop = document.getElementById('chapter-nav-host-desktop');
                    const mobile = document.getElementById('chapter-nav-host-mobile');
                    const nav = this.$refs.chapterNav;
                    if (!desktop || !mobile || !nav) return;
                    const isDesktop = window.matchMedia('(min-width: 768px)').matches;
                    const target = isDesktop ? desktop : mobile;
                    if (nav.parentElement !== target) {
                        target.innerHTML = '';
                        target.appendChild(nav);
                        nav.style.display = 'block';
                    }
                },
            };
        }

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
    <x-story::confirm-delete-chapter
        name="confirm-delete-chapter"
        :storySlug="$vm->story->slug"
        :chapterSlug="$vm->chapter->slug"
        :chapterTitle="$vm->chapter->title"
        :confirm="__('story::show.confirm_delete')" />
    @endif
</x-app-layout>