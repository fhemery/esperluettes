<div data-chapter-audio class="flex items-center justify-center gap-2 w-full">
    <audio controls preload="none" src="{{ route('fanart.file', $fanartId) }}" class="w-full max-w-md"
           aria-label="{{ __('fanart::ui.player.label') }}"></audio>
    @if ($canReport)
        <x-moderation::report-button topic-key="fanart" :entity-id="$fanartId" :compact="true" size="sm" />
    @endif
</div>
