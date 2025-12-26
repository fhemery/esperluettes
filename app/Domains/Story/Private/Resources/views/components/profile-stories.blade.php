<div class="flex flex-col gap-4">
    <div class="flex items-center justify-end sm:justify-between gap-4">
        <x-shared::title tag="h2" class="hidden sm:flex" icon="nest_eco_leaf">{{ __('story::profile.stories') }}</x-shared::title>
        <div class="flex items-center gap-3">
            @if($canCreateStory)
            <x-shared::popover placement="bottom" maxWidth="18rem">
                <x-slot name="trigger">
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-fg ring-1 ring-inset ring-gray-300">
                        <span class="material-symbols-outlined text-[16px] leading-none">menu_book</span>
                        <span>{{ (int)($availableChapterCredits ?? 0) }}</span>
                    </span>
                </x-slot>
                <div class="text-sm text-fg">
                    {{ trans_choice('story::profile.available-chapter-credits', (int)($availableChapterCredits ?? 0), ['count' => (int)($availableChapterCredits ?? 0)]) }}
                </div>
            </x-shared::popover>


            <x-shared::button color="accent">
                <a href="{{ route('stories.create') }}">
                    {{ __('story::profile.new-story') }}
                </a>
            </x-shared::button>
            @endif
        </div>
    </div>

    <x-story::list-grid :view-model="$viewModel" :display-authors="false" />
</div>
