<div data-chapter-audio class="flex items-center justify-center gap-2 w-full">
    <x-fanart::protected-audio preload="none" src="{{ route('fanart.file', $fanartId) }}" class="w-full max-w-md"
           aria-label="{{ __('fanart::ui.player.label') }}" />
    @if ($artistUrl)
        @php($artistLabel = __('fanart::ui.player.artist', ['name' => $artistName]))
        <a data-artist-link href="{{ $artistUrl }}" title="{{ $artistLabel }}" aria-label="{{ $artistLabel }}"
           class="inline-flex items-center justify-center w-6 h-6 text-gray-600 hover:text-accent">
            <span class="material-symbols-outlined" aria-hidden="true">account_circle</span>
        </a>
    @endif
    @if ($canReport)
        <x-moderation::report-button topic-key="fanart" :entity-id="$fanartId" :compact="true" size="sm" />
    @endif
</div>
