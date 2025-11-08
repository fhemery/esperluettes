@props([
    'chapters',
    'storySlug',
])

@php
    $chapters = $chapters ?? [];
@endphp

@if (!empty($chapters))
<div class="grid grid-cols-[auto_1fr_auto] gap-1">
    @foreach($chapters as $ch)
    {{-- Read toggle --}}
    <div class="col-span-1 surface-read text-on-surface flex items-center h-full"
         x-data="storyReadItem({
            storySlug: '{{ $storySlug }}',
            chapterSlug: '{{ $ch->slug }}',
            csrf: '{{ csrf_token() }}',
            isRead: {{ $ch->isRead ? 'true' : 'false' }},
         })"
         x-on:mark-read.stop="mark()"
         x-on:mark-unread.stop="unmark()">
        <x-shared::read-toggle :read="$ch->isRead" />
    </div>

    {{-- Chapter title --}}
    <div class="flex flex-col col-span-1 surface-read text-on-surface px-2 min-w-0">
        <a href="{{ $ch->url }}" class="flex-1 truncate text-fg hover:text-fg/80 font-semibold py-2">
            {{ $ch->title }}
        </a>
    </div>

    {{-- Words count --}}
    <div class="flex items-center h-full col-span-1 surface-read text-on-surface flex justify-center">
        <x-story::words-metric-badge
            size="sm"
            :nb-words="$ch->wordCount"
            :nb-characters="$ch->characterCount" />
    </div>

    @endforeach
</div>
@else
    <p class="text-sm text-fg/60">{{ __('readlist::page.chapters.empty') }}</p>
@endif
