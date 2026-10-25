<div data-chapter-audio class="flex items-center justify-center gap-2 w-full">
    <x-fanart::protected-audio preload="none" src="{{ route('fanart.file', $fanartId) }}" class="w-full max-w-md"
           aria-label="{{ __('fanart::ui.player.label') }}" />
    @if ($canReport)
        <x-moderation::report-button topic-key="fanart" :entity-id="$fanartId" :compact="true" size="sm" />
    @endif
</div>
