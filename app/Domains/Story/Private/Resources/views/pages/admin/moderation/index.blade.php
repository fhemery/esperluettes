<x-admin::layout>
    <div class="flex flex-col gap-6">
        <x-shared::title>{{ __('story::admin.moderation.title') }}</x-shared::title>

        {{-- Search bar --}}
        <form method="GET" action="{{ route('story.admin.moderation.index') }}" class="flex gap-2">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('story::admin.moderation.search_placeholder') }}"
                class="flex-1 border border-border rounded px-3 py-2 bg-surface text-on-surface focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded hover:bg-primary/90">
                <span class="material-symbols-outlined text-[20px] align-middle">search</span>
                {{ __('story::admin.moderation.search') }}
            </button>
            @if ($search)
                <a href="{{ route('story.admin.moderation.index') }}" class="px-4 py-2 border border-border rounded hover:bg-surface-read/50 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </a>
            @endif
        </form>

        {{-- Stories table --}}
        <div class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('story::admin.moderation.columns.title') }}</th>
                        <th class="p-3">{{ __('story::admin.moderation.columns.visibility') }}</th>
                        <th class="p-3">{{ __('story::admin.moderation.columns.authors') }}</th>
                        <th class="p-3 text-right">{{ __('story::admin.moderation.columns.collaborators') }}</th>
                        <th class="p-3 text-right">{{ __('story::admin.moderation.columns.chapters') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                @forelse ($stories as $story)
                    @php
                        $collaboratorCount = $story->collaborators->count();
                        $storySoloPrivateLocked = $story->visibility === \App\Domains\Story\Private\Models\Story::VIS_PRIVATE
                            && $collaboratorCount <= 1;
                        $storyNotVisible = $story->visibility === \App\Domains\Story\Private\Models\Story::VIS_PRIVATE
                            && !$story->isCollaborator($moderatorId);
                        $storyLink = $storyNotVisible
                            ? $adminModerationAccessUrl::story($story)
                            : route('stories.show', $story->slug);
                        $authors = $story->collaborators->where('role', 'author');
                        $authorNames = $authors->map(fn($c) => $profiles[$c->user_id]?->display_name ?? '#' . $c->user_id)->join(', ');
                    @endphp
                    {{-- Each story gets its own tbody so x-data scope covers both rows --}}
                    <tbody x-data="{ open: false, loading: false, html: '' }">
                        <tr class="border-b border-border/50">
                            <td class="p-3 font-medium">
                                <div class="flex items-center gap-2">
                                    @if ($storySoloPrivateLocked)
                                        <x-shared::popover placement="right">
                                            <x-slot name="trigger">
                                                <span class="material-symbols-outlined text-[16px] text-fg/40">lock</span>
                                            </x-slot>
                                            <p>{{ __('story::admin.moderation.private_story_locked') }}</p>
                                        </x-shared::popover>
                                        <span>{{ $story->title }}</span>
                                    @else
                                        @if ($storyNotVisible)
                                            <span class="material-symbols-outlined text-[16px] text-fg/40" title="{{ __('story::admin.moderation.not_visible_title') }}">lock</span>
                                        @endif
                                        <a href="{{ $storyLink }}" class="hover:underline">{{ $story->title }}</a>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3">
                                @if ($story->visibility === \App\Domains\Story\Private\Models\Story::VIS_PUBLIC)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-success/20 text-success rounded text-sm">
                                        <span class="material-symbols-outlined text-[14px]">public</span>
                                        {{ __('story::admin.moderation.visibility.public') }}
                                    </span>
                                @elseif ($story->visibility === \App\Domains\Story\Private\Models\Story::VIS_COMMUNITY)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-primary/20 text-primary rounded text-sm">
                                        <span class="material-symbols-outlined text-[14px]">groups</span>
                                        {{ __('story::admin.moderation.visibility.community') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-fg/10 text-fg/60 rounded text-sm">
                                        <span class="material-symbols-outlined text-[14px]">lock</span>
                                        {{ __('story::admin.moderation.visibility.private') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-fg/80 text-sm">{{ $authorNames ?: '-' }}</td>
                            <td class="p-3 text-right text-fg/60">{{ $collaboratorCount }}</td>
                            <td class="p-3 text-right text-fg/60">{{ $story->chapters_count }}</td>
                            <td class="p-3">
                                @unless ($storySoloPrivateLocked)
                                <button
                                    @click="
                                        if (open) {
                                            open = false;
                                        } else {
                                            loading = true;
                                            fetch('{{ route('story.admin.moderation.chapters', $story->id) }}')
                                                .then(r => r.text())
                                                .then(h => { html = h; open = true; loading = false; })
                                                .catch(() => { loading = false; });
                                        }
                                    "
                                    class="flex items-center gap-1 text-sm text-primary hover:text-primary/80"
                                    :disabled="loading"
                                >
                                    <span class="material-symbols-outlined text-[18px]" x-text="loading ? 'hourglass_empty' : (open ? 'expand_less' : 'expand_more')">expand_more</span>
                                    {{ __('story::admin.moderation.chapters_button') }}
                                </button>
                                @endunless
                            </td>
                        </tr>
                        <tr x-show="open" x-cloak class="bg-surface/30">
                            <td colspan="6" class="px-6 py-3" x-html="html"></td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">
                                {{ __('story::admin.moderation.no_stories') }}
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <x-shared::pagination :paginator="$stories" />
    </div>
</x-admin::layout>
