@props([
    'storySlug',
    'canCreateChapter' => false,
])

<div class="flex items-center gap-2" data-test-id="createChapterBtn">
    @if(!$canCreateChapter)
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
        <a href="{{ route('chapters.create', ['storySlug' => $storySlug]) }}">
            <x-shared::button color="accent">
                <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                {{ __('story::chapters.sections.add_chapter') }}
            </x-shared::button>
        </a>
    @endif
</div>
