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
    <!-- Read toggle, only for logged users -->
    @auth
    <div class="col-span-1 surface-read text-on-surface p-2 flex items-center h-full"
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
    @else
    <div></div>
    @endauth

    <!-- Chapter title -->
    <div class="flex flex-col col-span-1 surface-read text-on-surface p-2 min-w-0">
        <a href="{{ $ch->url }}" class="flex-1 truncate text-fg hover:text-fg/80 font-semibold py-2">
            {{ $ch->title }}
        </a>
    </div>

    <!-- Words count -->
    <div class="flex items-center h-full col-span-1 surface-read text-on-surface p-2 flex justify-center">
        <x-story::words-metric-badge
            size="sm"
            :nb-words="$ch->wordCount"
            :nb-characters="$ch->characterCount" />
    </div>

    @endforeach
</div>

@once
@push('scripts')
<script>
    (function(){
        if (window.storyReadItem) return;

        function buildUrl(storySlug, chapterSlug) {
            return `/stories/${encodeURIComponent(storySlug)}/chapters/${encodeURIComponent(chapterSlug)}/read`;
        }

        window.storyReadItem = function({ storySlug, chapterSlug, csrf, isRead }) {
            return {
                isRead: !!isRead,
                async mark() {
                    try {
                        const res = await fetch(buildUrl(storySlug, chapterSlug), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'text/plain',
                            },
                        });
                        if (res.status === 204) {
                            this.isRead = true;
                        }
                    } catch (e) {
                    }
                },
                async unmark() {
                    try {
                        const res = await fetch(buildUrl(storySlug, chapterSlug), {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'text/plain',
                            },
                        });
                        if (res.status === 204) {
                            this.isRead = false;
                        }
                    } catch (e) {
                    }
                },
            };
        };
    })();
</script>
@endpush

@endonce
@else
    <p class="text-sm text-fg/60">{{ __('readlist::page.chapters.empty') }}</p>
@endif
