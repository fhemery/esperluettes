@if ($visible)
    @php($link = 'inline-flex items-center justify-center rounded-md font-medium px-4 py-2 text-sm surface-accent text-on-surface border-surface hover:border-surface/90')
    <div class="flex flex-wrap items-center gap-2" data-fanart-actions>
        @if ($canSubmit)
            <a href="{{ route('fanart.create', $storySlug) }}" class="{{ $link }}">{{ __('fanart::ui.actions.submit') }}</a>
        @endif
        @if ($canArtwork)
            <a href="{{ route('fanart.create', $storySlug) }}" class="{{ $link }}">{{ __('fanart::ui.actions.artwork') }}</a>
        @endif
        <x-fanart::request-role-button :story-id="$storyId" />
    </div>
@endif
