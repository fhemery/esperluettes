@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@if (!empty($chapters))
<ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white">
    @foreach($chapters as $ch)
    <li class="p-3 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            @auth
            <button type="button"
                class="read-toggle inline-flex items-center justify-center rounded-full w-6 h-6"
                data-story-slug="{{ $story->slug }}"
                data-chapter-slug="{{ $ch->slug }}"
                data-read="{{ $ch->isRead ? '1' : '0' }}"
                data-label-read="{{ __('story::chapters.actions.marked_read') }}"
                data-label-unread="{{ __('story::chapters.actions.mark_as_read') }}"
                aria-label="{{ $ch->isRead ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}"
                title="{{ $ch->isRead ? __('story::chapters.actions.marked_read') : __('story::chapters.actions.mark_as_read') }}">
                <span class="material-symbols-outlined text-[20px] leading-none {{ $ch->isRead ? 'text-green-700' : 'text-gray-300' }}">check_circle</span>
            </button>
            @endauth
            <a href="{{ $ch->url }}" class="text-indigo-700 hover:text-indigo-900 font-medium">
                {{ $ch->title }}
            </a>
        </div>
        <div class="flex items-center gap-3">
            <x-shared::metric-badge
                icon="visibility"
                :value="$ch->readsLogged"
                :label="__('story::chapters.reads.label')"
                :tooltip="__('story::chapters.reads.tooltip')"
            />

            <x-shared::metric-badge
                icon="article"
                :value="$ch->wordCount"
                :label="__('story::chapters.words.label')"
                :tooltip="__('story::chapters.words.tooltip')"
            />
        </div>
    </li>
    @endforeach
</ul>
@else
<p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
@endif

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
                icon.classList.add('text-green-700');
                icon.classList.remove('text-gray-300');
            } else {
                icon.classList.remove('text-green-700');
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