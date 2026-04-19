@php
    $storyPrivateNoAccess = $story->visibility === \App\Domains\Story\Private\Models\Story::VIS_PRIVATE
        && !$story->isCollaborator($moderatorId);
    $moderatorIsAuthor = $story->isAuthor($moderatorId);
@endphp

@if ($chapters->isEmpty())
    <p class="text-fg/50 text-sm py-2">{{ __('story::admin.moderation.no_chapters') }}</p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-border/30 text-left text-fg/60">
                <th class="py-2 pr-4 font-normal">{{ __('story::admin.moderation.columns.title') }}</th>
                <th class="py-2 pr-4 font-normal">{{ __('story::admin.moderation.columns.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chapters as $chapter)
                @php
                    $chapterNotVisible = $storyPrivateNoAccess
                        || ($chapter->status === \App\Domains\Story\Private\Models\Chapter::STATUS_NOT_PUBLISHED && !$moderatorIsAuthor);
                    $chapterLink = $chapterNotVisible
                        ? route('story.admin.moderation.chapter-access', $chapter->id)
                        : route('chapters.show', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]);
                @endphp
                <tr class="border-b border-border/20 hover:bg-surface/50">
                    <td class="py-2 pr-4">
                        <div class="flex items-center gap-2">
                            @if ($chapterNotVisible)
                                <span class="material-symbols-outlined text-[14px] text-fg/40" title="{{ __('story::admin.moderation.not_visible_title') }}">lock</span>
                            @endif
                            <a href="{{ $chapterLink }}" class="hover:underline">{{ $chapter->title }}</a>
                        </div>
                    </td>
                    <td class="py-2 pr-4">
                        @if ($chapter->status === \App\Domains\Story\Private\Models\Chapter::STATUS_PUBLISHED)
                            <span class="inline-flex items-center gap-1 text-success text-xs">
                                <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                {{ __('story::admin.moderation.chapter_published') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-fg/50 text-xs">
                                <span class="material-symbols-outlined text-[12px]">unpublished</span>
                                {{ __('story::admin.moderation.chapter_unpublished') }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
