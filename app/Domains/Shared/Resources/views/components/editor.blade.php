@props([
  'name',
  'id',
  'defaultValue' => '',
  'max' => null,
  'min' => null,
  'nbLines' => 5,
  'placeholder' => '',
  'isMandatory' => false,
  'indentParagraphs' => false,
  'resizable' => true,
  'toolbar' => ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji'],
])

@php
  $toolbar = array_values($toolbar);
  $hasLink = in_array('link', $toolbar, true);
  $hasSpoiler = in_array('spoiler', $toolbar, true);
  $toolbarJson = json_encode($toolbar, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<div {{ $attributes->merge(['class' => 'rich-content w-full']) }} @if($hasLink) data-link-visit="{{ __('shared::editor.link_visit') }}" data-link-enter="{{ __('shared::editor.link_enter') }}" data-link-edit="{{ __('shared::editor.link_edit') }}" data-link-save="{{ __('shared::editor.link_save') }}" data-link-remove="{{ __('shared::editor.link_remove') }}" @endif>
    <div class="surface-read text-on-surface w-full {{ $indentParagraphs ? 'ql-indent' : '' }}">
      <div id="{{ $id }}" data-placeholder="{{ $placeholder }}" data-nb-lines="{{ $nbLines }}" data-is-mandatory="{{ $isMandatory ? 'true' : 'false' }}" data-clean-label="{{ __('shared::editor.clean') }}" data-resizable="{{ $resizable ? 'true' : 'false' }}" data-toolbar="{{ $toolbarJson }}" @if($hasSpoiler) data-spoiler-label="{{ __('shared::editor.spoiler') }}" @endif @if($min) data-min="{{ (int) $min }}" @endif @if($max) data-max="{{ (int) $max }}" @endif></div>
    </div>
    <textarea class="hidden" name="{{ $name }}" id="quill-editor-area-{{ $id }}">{!! old($name, $defaultValue) !!}</textarea>
    <div class="mt-2 text-xs text-right " id="quill-counter-wrap-{{ $id }}">
        <span id="quill-counter-{{ $id }}">0</span>
        @if($max)
            <span>/ {{ $max }}</span>
        @endif
        <span id="quill-unit-{{ $id }}"
              data-singular="{{ trans_choice('shared::editor.character', 1) }}"
              data-plural="{{ trans_choice('shared::editor.character', 2) }}">
            {{ trans_choice('shared::editor.character', 2) }}
        </span>
        @if($min)
            <span>({{ trans_choice('shared::editor.min-characters', (int) $min, ['min' => (int) $min]) }})</span>
        @endif
    </div>
    @push('scripts')
    <script>
      // Initialize the editor instance via shared Vite-bundled initializer.
      // Pass nbLines to preserve sizing behavior.
      (function(){
        if (window.initQuillEditor) {
          window.initQuillEditor('{{ $id }}');
        } else {
          // Fallback: ensure init runs after scripts load
          document.addEventListener('DOMContentLoaded', function(){
            if (window.initQuillEditor) {
              window.initQuillEditor('{{ $id }}');
            }
          });
        }
      })();
    </script>
    @endpush
</div>
