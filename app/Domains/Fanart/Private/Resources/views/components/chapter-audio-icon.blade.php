@if ($visible)
    <button type="button" data-chapter-audio-icon="{{ $chapterId }}"
            class="inline-flex items-center shrink-0 rounded hover:text-accent"
            :class="$store.fanartPlayer.currentId === {{ $chapterId }} ? 'text-accent' : ''"
            :aria-pressed="($store.fanartPlayer.currentId === {{ $chapterId }}).toString()"
            x-on:click="$store.fanartPlayer.play({{ $chapterId }})"
            title="{{ __('fanart::ui.player.listen') }}"
            aria-label="{{ __('fanart::ui.player.listen') }}">
        <span class="material-symbols-outlined text-[20px] leading-none" aria-hidden="true"
              x-text="$store.fanartPlayer.currentId === {{ $chapterId }} ? 'volume_up' : 'play_circle'">play_circle</span>
    </button>
@endif
