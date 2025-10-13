<div class="flex flex-col gap-4 items-center justify-between surface-read text-on-surface p-4 h-full">
    <x-shared::title tag="h2" icon="book_ribbon">
        {{ __('story::keep-reading.title') }}
    </x-shared::title>

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