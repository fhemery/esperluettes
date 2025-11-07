@props(['chaptersViewModel', 'story'])

@if ($chaptersViewModel->isEmpty)
    <p class="h-full w-full flex items-center justify-center text-sm text-fg/60">
        {{ __('readlist::page.chapters.empty') }}</p>
@else
    <div class="flex flex-col gap-2">
    @if ($chaptersViewModel->chaptersBefore > 0)
        <p class="text-sm text-fg/70">
            {{ trans_choice('readlist::page.chapters.before', $chaptersViewModel->chaptersBefore, ['count' => $chaptersViewModel->chaptersBefore]) }}
        </p>
    @endif

    <x-read-list::read-list-chapter-list :chapters="$chaptersViewModel->chapters" :story-slug="$story->slug" />

    @if ($chaptersViewModel->chaptersAfter > 0)
        <p class="text-sm text-fg/70">
            {{ trans_choice('readlist::page.chapters.after', $chaptersViewModel->chaptersAfter,  ['count' => $chaptersViewModel->chaptersAfter]) }}
        </p>
    @endif
    </div>
@endif
