<section class="mt-10" x-data="{ editing: false }">
    <div class="flex items-center justify-between mb-4">
        <x-shared::title tag="h2">{{ __('story::chapters.sections.chapters') }}</x-shared::title>
        <div class="flex items-center gap-2">
            <template x-if="!editing">
                <div class="flex items-center gap-2">
                    @if (!empty($chapters))
                    <x-shared::button color="neutral" :outline="true" @click="editing = true">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('story::chapters.actions.reorder') }}
                    </x-shared::button>
                    @endif
                    <x-story::chapter.create-button :storySlug="$story->slug" :canCreateChapter="$availableChapterCredits > 0" />
                </div>
            </template>
            <template x-if="editing">
                <div class="flex items-center gap-2">
                    <x-shared::button color="accent" icon="save" x-on:click="window.dispatchEvent(new CustomEvent('chapters-reorder-save'))">
                        {{ __('story::chapters.actions.save_order') }}
                    </x-shared::button>
                    <x-shared::button color="neutral" icon="close" x-on:click="window.dispatchEvent(new CustomEvent('chapters-reorder-cancel')); editing = false">
                        {{ __('story::chapters.actions.cancel') }}
                    </x-shared::button>
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