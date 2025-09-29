<div class="flex flex-col gap-4 items-center justify-between surface-read text-on-surface p-4 h-full">
    <h3 class="flex items-center self-start gap-2 text-accent font-semibold text-xl">
        <span class="material-symbols-outlined">
            book_ribbon
        </span>{{ __('story::keep-reading.title') }}
    </h3>

    @if($story === null)
    <p>{{ __('story::keep-reading.empty') }}</p>
    <a href="{{ route('stories.index') }}">
        <x-shared::button color="accent">
            {{ __('story::keep-reading.go_read') }}
        </x-shared::button>
    </a>
    @else

    <x-story::card :item="$story" :display-authors="false" />

    <div class="mt-4 flex gap-2">
        <a href="{{ $nextChapterUrl }}">
            <x-shared::button color="accent">
                {{ __('story::keep-reading.continue') }}
            </x-shared::button>
        </a>
    </div>
    @endif
</div>