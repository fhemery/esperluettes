@php
    $isAudio = $fanart->kind === \App\Domains\Fanart\Private\Support\FanartKind::Audio;
    $artist = $details['artist'];
@endphp
<div class="flex-1 flex flex-col md:flex-row md:items-center gap-4 p-4 pt-12 md:pt-4" data-fanart-viewer="{{ $fanart->id }}">
    <div class="md:w-3/5 flex items-center justify-center">
        @if ($isAudio)
            <audio controls src="{{ route('fanart.file', $fanart->id) }}" class="w-full"
                   aria-label="{{ $fanart->title }}"></audio>
        @else
            <img src="{{ route('fanart.file', $fanart->id) }}" alt="{{ $fanart->title }}"
                 class="max-w-full max-h-[calc(100vh-6rem)] object-contain">
        @endif
    </div>

    <div class="md:w-2/5 flex flex-col gap-3">
        <x-shared::title tag="h2">{{ $fanart->title }}</x-shared::title>

        @if ($fanart->status === \App\Domains\Fanart\Private\Support\FanartStatus::Pending)
            <x-shared::badge color="warning">{{ __('fanart::ui.viewer.pending_badge') }}</x-shared::badge>
        @endif

        @if ($artist)
            <p>
                {{ __('fanart::ui.viewer.artist') }}
                <a href="{{ route('profile.show', ['profile' => $artist->slug]) }}"
                   class="font-medium underline hover:text-accent">{{ $artist->display_name }}</a>
            </p>
        @endif

        @if ($details['chapterTitle'])
            <p class="text-sm">{{ __('fanart::ui.viewer.chapter', ['title' => $details['chapterTitle']]) }}</p>
        @endif

        @if ($details['description'])
            <div class="prose max-w-none">{!! $details['description'] !!}</div>
        @endif

        @if ($canReview)
            <form action="{{ route('fanart.accept', $fanart->id) }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <label for="fanart-review-comment-{{ $fanart->id }}" class="text-sm font-medium">
                    {{ __('fanart::ui.viewer.review_comment') }}
                </label>
                <textarea id="fanart-review-comment-{{ $fanart->id }}" name="comment" required maxlength="500" rows="3"
                          class="w-full border border-accent rounded p-2"></textarea>
                <div class="flex gap-2">
                    <x-shared::button type="submit" color="success">{{ __('fanart::ui.viewer.accept') }}</x-shared::button>
                    <x-shared::button type="submit" color="error"
                                      formaction="{{ route('fanart.decline', $fanart->id) }}">
                        {{ __('fanart::ui.viewer.decline') }}
                    </x-shared::button>
                </div>
            </form>
        @endif

        <div class="flex items-center justify-between gap-2 mt-auto">
            @if ($canDelete)
                <form action="{{ route('fanart.destroy', $fanart->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-shared::button type="submit" color="error">{{ __('fanart::ui.viewer.delete') }}</x-shared::button>
                </form>
            @endif

            @if ($canModerate)
                <x-moderation::moderation-button badgeColor="warning" position="top">
                    <x-moderation::action
                        :action="route('fanart.moderation.destroy', $fanart->id)"
                        method="DELETE"
                        :label="__('fanart::ui.viewer.moderation_delete')" />
                </x-moderation::moderation-button>
            @endif

            @if ($canReport)
                <x-moderation::report-button topic-key="fanart" :entity-id="$fanart->id" :compact="true" size="sm" />
            @endif
        </div>
    </div>
</div>
