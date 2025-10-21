<div class="flex flex-col gap-4 items-center surface-read text-on-surface p-4 h-full">
    <x-shared::title tag="h2" icon="book_ribbon">
        {{ __('story::keep-reading.title') }}
    </x-shared::title>

    @if($story === null)
    <div class="flex-1 flex flex-col justify-center">
        <p>{{ __('story::keep-reading.empty') }}</p>
    </div>
    <a href="{{ route('stories.index') }}">
        <x-shared::button color="accent">
            {{ __('story::keep-reading.go_read') }}
        </x-shared::button>
    </a>
    @else

    <div class="flex-1 flex flex-col">
        <x-story::card :item="$story" :display-authors="false" />
    </div>

    <div class="flex gap-2">
        <a href="{{ $nextChapterUrl }}">
            <x-shared::button color="accent">
                {{ __('story::keep-reading.continue') }}
            </x-shared::button>
        </a>
    </div>
    @endif
</div>