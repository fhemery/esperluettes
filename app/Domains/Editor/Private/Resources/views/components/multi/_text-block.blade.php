{{--
    One text block of the multi-editor. Included both for initial blocks (real
    $uid) and inside the hidden template ($uid='__UID__'). Quill is initialized
    by the parent multiEditor Alpine component (init / _afterInsert), not here,
    so load order can't leave an uninitialized editor.

    Vars: $name (base), $uid, $toolbar (array), $min, $max, $html, $placeholder,
          $nbLines, $indentParagraphs — the last two mirror <x-editor::rich-text>
          so a block is the same writing surface as a simple-mode field.
--}}
@php
    $editorId = preg_replace('/[^A-Za-z0-9_]/', '_', $name . '__' . $uid);
    $toolbarJson = json_encode(array_values($toolbar), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<div class="ce-block ce-block--text border border-border rounded-lg p-3 mb-3 relative" data-block data-type="text" data-uid="{{ $uid }}">
    <div class="flex items-center justify-end gap-1 mb-2 text-fg/60">
        <button type="button" x-on:click="moveUp($el)" class="p-1 hover:text-fg" :title="labels.up"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></button>
        <button type="button" x-on:click="moveDown($el)" class="p-1 hover:text-fg" :title="labels.down"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></button>
        <button type="button" x-on:click="removeBlock($el)" class="p-1 hover:text-error" :title="labels.delete"><span class="material-symbols-outlined text-[18px]">delete</span></button>
    </div>

    <input type="hidden" name="{{ $name }}[{{ $uid }}][type]" value="text">

    <div class="rich-content w-full">
        <div class="surface-read text-on-surface w-full{{ ($indentParagraphs ?? false) ? ' ql-indent' : '' }}">
            <div id="{{ $editorId }}" data-placeholder="{{ $placeholder ?? '' }}" data-nb-lines="{{ $nbLines ?? 5 }}" data-is-mandatory="false" data-resizable="true" data-toolbar="{{ $toolbarJson }}" @if($min) data-min="{{ (int) $min }}" @endif @if($max) data-max="{{ (int) $max }}" @endif></div>
        </div>
        <textarea class="hidden" name="{{ $name }}[{{ $uid }}][html]" id="quill-editor-area-{{ $editorId }}">{!! $html ?? '' !!}</textarea>
    </div>

    @include('editor::components.multi._insert-affordance')
</div>
