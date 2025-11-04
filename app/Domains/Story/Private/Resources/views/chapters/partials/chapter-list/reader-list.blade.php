@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if (!empty($chapters))
<div class="grid grid-cols-[auto_1fr] sm:grid-cols-[auto_1fr_auto_auto_auto_auto] gap-1">
    @foreach($chapters as $ch)
    <!-- Read toggle, only for logged users -->
    @auth
    <div class="col-span-1 surface-read text-on-surface p-2 flex items-center h-full"
         x-data="storyReadItem({
            storySlug: '{{ $story->slug }}',
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
    <!-- On mobile, also add updated at, words count, and reads count -->
    <div class="flex flex-col col-span-1 surface-read text-on-surface p-2 min-w-0">
        <a href="{{ $ch->url }}" class="flex-1 truncate text-fg hover:text-fg/80 font-semibold py-2">
            {{ $ch->title }}
        </a>

        <div class="sm:hidden flex flex-start gap-3" x-data="{ updated: new Date('{{ $ch->updatedAt }}') }">
            <span class="text-sm" x-text="DateUtils.formatDate(updated)"></span>
            <x-story::words-metric-badge
                size="xs"
                :nb-words="$ch->wordCount"
                :nb-characters="$ch->characterCount" />
            <x-shared::metric-badge
                icon="comment"
                :value="$ch->commentCount"
                size="xs"
                :label="__('story::chapters.comments.label')"
                />
            <x-shared::metric-badge
                icon="visibility"
                :value="$ch->readsLogged"
                size="xs"
                :label="__('story::chapters.reads.label')"
                :tooltip="__('story::chapters.reads.tooltip')" />
        </div>
    </div>

    <!-- Updated at -->
    <div class="hidden sm:flex items-center h-full col-span-1 surface-read text-on-surface p-2" x-data="{ updated: new Date('{{ $ch->updatedAt }}') }">
        <span x-text="DateUtils.formatDate(updated)"></span>
    </div>

    <!-- Words count -->
    <div class="hidden sm:flex items-center h-full col-span-1 surface-read text-on-surface p-2 flex justify-center">
        <x-story::words-metric-badge
            size="sm"
            :nb-words="$ch->wordCount"
            :nb-characters="$ch->characterCount" />
    </div>

    <!-- Comments count -->
    <div class="hidden sm:flex items-center h-full col-span-1 surface-read text-on-surface p-2">
        <x-shared::metric-badge
            icon="comment"
            :value="$ch->commentCount"
            size="sm"
            :label="__('story::chapters.comments.label')"
            />
    </div>

    <!-- Reads count -->
    <div class="hidden sm:flex items-center h-full col-span-1 surface-read text-on-surface p-2">
        <x-shared::metric-badge
            icon="visibility"
            :value="$ch->readsLogged"
            size="sm"
            :label="__('story::chapters.reads.label')"
            :tooltip="__('story::chapters.reads.tooltip')" />
    </div>

    @endforeach
    @else
    <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
    @endif
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