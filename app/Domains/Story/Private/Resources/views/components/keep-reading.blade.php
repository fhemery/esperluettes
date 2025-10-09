<div class="flex flex-col items-center justify-between gap-4 surface-read text-on-surface p-4 h-full w-full max-w-[400px] mx-auto">
    <h3 class="flex items-center self-center gap-2 text-accent font-semibold text-xl">
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

    <x-story::card class="w-full max-w-md" :item="$story" :display-authors="false" />
    
    <div class="flex justify-center items-center gap-2 w-full mt-4">
        <a href="{{ $nextChapterUrl }}">
            <x-shared::button color="accent">
                {{ __('story::keep-reading.continue') }}
            </x-shared::button>
        </a>
    </div>
    @endif
</div>