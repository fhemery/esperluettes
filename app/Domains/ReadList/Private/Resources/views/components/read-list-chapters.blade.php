@props([
    'chaptersViewModel',
    'story',
])

@if($chaptersViewModel->isEmpty)
    <p class="text-sm text-fg/60">{{ __('readlist::page.chapters.empty') }}</p>
@else
    @if($chaptersViewModel->chaptersBefore > 0)
        <p class="text-sm text-fg/60 mb-2">
            {{ __('readlist::page.chapters.before', ['count' => $chaptersViewModel->chaptersBefore]) }}
        </p>
    @endif
    
    <x-read-list::read-list-chapter-list 
        :chapters="$chaptersViewModel->chapters"
        :story-slug="$story->slug" />
    
    @if($chaptersViewModel->chaptersAfter > 0)
        <p class="text-sm text-fg/60 mt-2">
            {{ __('readlist::page.chapters.after', ['count' => $chaptersViewModel->chaptersAfter]) }}
        </p>
    @endif
@endif
