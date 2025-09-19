<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold text-gray-900">
        {{ $canEdit ? __('story::profile.my-stories') : __('story::profile.stories') }}
    </h2>

    <div class="flex items-center gap-3">
        <x-shared::popover placement="bottom" maxWidth="18rem">
            <x-slot name="trigger">
                <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-900 ring-1 ring-inset ring-gray-300">
                    <span class="material-symbols-outlined text-[16px] leading-none">menu_book</span>
                    <span>{{ (int)($availableChapterCredits ?? 0) }}</span>
                </span>
            </x-slot>
            <div class="text-sm text-gray-800">
                {{ __('story::profile.available-chapter-credits') }}
            </div>
        </x-shared::popover>

        @if($canCreateStory)
            <a href="{{ route('stories.create') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                {{ __('story::profile.new-story') }}
            </a>
        @endif
    </div>
</div>

<x-story::list-grid :view-model="$viewModel" :display-authors="false"/>
