<section class="mt-10" x-data="{ editing: false }">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ __('story::chapters.sections.chapters') }}</h2>
        <div class="flex items-center gap-2">
            <template x-if="!editing">
                <div class="flex items-center gap-2">
                    @if (!empty($chapters))
                    <x-shared::button color="neutral" :outline="true" @click="editing = true">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('story::chapters.actions.reorder') }}
                    </x-shared::button>
                    @endif
                    @php($avail = isset($availableChapterCredits) ? (int)$availableChapterCredits : 0)
                    <div class="flex items-center gap-2">

                        @if($avail <= 0)
                            <x-shared::button color="accent" disabled="true">
                                <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                                {{ __('story::chapters.sections.add_chapter') }}
                            </x-shared::button>

                            <x-shared::tooltip icon="info" placement="top" maxWidth="18rem">
                                <div class="text-sm text-fg">
                                    {{ __('story::chapters.no_chapter_credits_left') }}
                                </div>
                            </x-shared::tooltip>
                        @else
                        <a href="{{ route('chapters.create', ['storySlug' => $story->slug]) }}">
                            <x-shared::button color="accent">
                                <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                                {{ __('story::chapters.sections.add_chapter') }}
                            </x-shared::button>
                        </a>
                        @endif
                    </div>
                </div>
            </template>
            <template x-if="editing">
                <div class="flex items-center gap-2">
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('chapters-reorder-save'))"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span class="material-symbols-outlined text-[18px] leading-none">save</span>
                        {{ __('story::chapters.actions.save_order') }}
                    </button>
                    <button type="button" @click="window.dispatchEvent(new CustomEvent('chapters-reorder-cancel')); editing = false"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[18px] leading-none">close</span>
                        {{ __('story::chapters.actions.cancel') }}
                    </button>
                </div>
            </template>
        </div>
    </div>

    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @if (empty($chapters))
    <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
    @else
    <div x-show="!editing">
        @include('story::chapters.partials.chapter-list.author-list', ['story' => $story, 'chapters' => $chapters])
    </div>
    <div x-show="editing">
        @include('story::chapters.partials.chapter-list.reorder-list', ['story' => $story, 'chapters' => $chapters])
    </div>
    @endif
</section>