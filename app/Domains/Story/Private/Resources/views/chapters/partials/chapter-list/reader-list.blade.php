@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if (!empty($chapters))
<div class="grid grid-cols-[auto_1fr] sm:grid-cols-[auto_1fr_auto_auto_auto_auto] gap-2">
    @foreach($chapters as $ch)
    <!-- Read toggle, only for logged users -->
    @auth
    <div class="col-span-1 surface-read text-on-surface p-2 flex items-center h-full">
        <button type="button"
            class="read-toggle inline-flex items-center justify-center rounded-full w-10 h-10"
            data-story-slug="{{ $story->slug }}"
            data-chapter-slug="{{ $ch->slug }}"
            data-read="{{ $ch->isRead ? '1' : '0' }}"
            data-label-read="{{ __('story::chapters.actions.marked_read') }}"
            data-label-unread="{{ __('story::chapters.actions.mark_as_read') }}"
            aria-label="{{ $ch->isRead ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}"
            title="{{ $ch->isRead ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}">
            <span class="material-symbols-outlined text-[30px] leading-none {{ $ch->isRead ? 'text-success' : 'text-gray-300' }}">check_circle</span>
        </button>
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
    (function() {
        function getCsrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function buildUrl(storySlug, chapterSlug) {
            return `/stories/${encodeURIComponent(storySlug)}/chapters/${encodeURIComponent(chapterSlug)}/read`;
        }

        function updateIcon(btn, isRead) {
            const icon = btn.querySelector('.material-symbols-outlined');
            if (!icon) return;
            icon.textContent = 'check_circle';
            // Apply colors via style to avoid JIT/class issues
            if (isRead) {
                icon.classList.add('text-success');
                icon.classList.remove('text-gray-300');
            } else {
                icon.classList.remove('text-success');
                icon.classList.add('text-gray-300');
            }
            btn.setAttribute('data-read', isRead ? '1' : '0');
            const labelRead = btn.getAttribute('data-label-read') || 'Read';
            const labelUnread = btn.getAttribute('data-label-unread') || 'Mark as read';
            btn.setAttribute('aria-label', isRead ? labelRead : labelUnread);
            btn.setAttribute('title', isRead ? labelRead : labelUnread);
        }

        async function toggle(btn) {
            const inProgress = btn.getAttribute('data-busy') === '1';
            if (inProgress) return;
            btn.setAttribute('data-busy', '1');

            const story = btn.getAttribute('data-story-slug');
            const chapter = btn.getAttribute('data-chapter-slug');
            const isRead = btn.getAttribute('data-read') === '1';
            const url = buildUrl(story, chapter);
            const method = isRead ? 'DELETE' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': getCsrf(),
                        'Accept': 'text/plain',
                    },
                });
                if (res.status === 204) {
                    updateIcon(btn, !isRead);
                }
            } catch (e) {
                // noop
            } finally {
                btn.setAttribute('data-busy', '0');
            }
        }

        function bind() {
            document.querySelectorAll('button.read-toggle').forEach((btn) => {
                btn.addEventListener('click', () => toggle(btn));
            });
        }

        bind();
    })();
</script>
@endpush

@endonce