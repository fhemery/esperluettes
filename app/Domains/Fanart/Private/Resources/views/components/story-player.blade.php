{{-- Seeds Alpine.store('fanartPlayer'); the speaker icons of the chapter rows drive this single <audio>. --}}
<div data-fanart-player x-cloak
     data-entries="{{ json_encode($entries, JSON_UNESCAPED_SLASHES) }}"
     x-data
     x-init="$store.fanartPlayer.attach({ audio: $refs.audio, csrf: @js(csrf_token()), entries: JSON.parse($el.dataset.entries) })"
     x-show="$store.fanartPlayer.currentId !== null"
     class="mb-2">
    <x-fanart::protected-audio x-ref="audio" preload="none" class="w-full"
           x-on:ended="$store.fanartPlayer.onEnded()"
           aria-label="{{ __('fanart::ui.player.label') }}" />
</div>

@once
    @push('head-scripts')
        @vite('app/Domains/Fanart/Private/Resources/js/fanart-player.js')
    @endpush
@endonce
