{{--
    One audio block of the multi-editor (opt-in `audio` type). Chrome comes from
    <x-editor::multi.block>. Posts blocks[uid][path|sound|sound_remove|caption].

    Vars: $name (base), $uid, $block (array|null)
--}}
@inject('mediaApi', 'App\Domains\Media\Public\Api\MediaPublicApi')
@php
    $audioPath = $block['path'] ?? null;
@endphp
<x-editor::multi.block type="audio" :name="$name" :uid="$uid">
    <input type="hidden" name="{{ $name }}[{{ $uid }}][path]" value="{{ $audioPath }}">

    <x-media::sound-field
        :name="$name.'['.$uid.'][sound]'"
        :remove-name="$name.'['.$uid.'][sound_remove]'"
        accept="audio/mpeg"
        :max-size="10240"
        :removable="true"
        :preview-url="$audioPath ? $mediaApi->originalUrl($audioPath) : null" />

    <label class="block mt-2 text-sm">
        <span class="text-fg/70">{{ __('editor::multi.audio_caption') }}</span>
        <input type="text" maxlength="255" name="{{ $name }}[{{ $uid }}][caption]" value="{{ $block['caption'] ?? '' }}"
            class="mt-1 w-full border border-border rounded-md px-2 py-1">
    </label>
</x-editor::multi.block>
