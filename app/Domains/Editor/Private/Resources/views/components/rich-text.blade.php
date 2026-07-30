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
  'toolbar' => 'default',
])

@use(App\Domains\Editor\Private\Support\ToolbarPresets)

@php
  // A string names a preset; an array is used as-is (presets bypassed).
  $toolbar = ToolbarPresets::resolve($toolbar);
  $hasLink = in_array('link', $toolbar, true);
  $hasSpoiler = in_array('spoiler', $toolbar, true);
  $toolbarJson = json_encode($toolbar, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<div {{ $attributes->merge(['class' => 'rich-content w-full']) }} @if($hasLink) data-link-visit="{{ __('editor::rich-text.link_visit') }}" data-link-enter="{{ __('editor::rich-text.link_enter') }}" data-link-edit="{{ __('editor::rich-text.link_edit') }}" data-link-save="{{ __('editor::rich-text.link_save') }}" data-link-remove="{{ __('editor::rich-text.link_remove') }}" @endif>
    <div class="surface-read text-on-surface w-full {{ $indentParagraphs ? 'ql-indent' : '' }}">
      <div id="{{ $id }}" data-placeholder="{{ $placeholder }}" data-nb-lines="{{ $nbLines }}" data-is-mandatory="{{ $isMandatory ? 'true' : 'false' }}" data-clean-label="{{ __('editor::rich-text.clean') }}" data-resizable="{{ $resizable ? 'true' : 'false' }}" data-toolbar="{{ $toolbarJson }}" @if($hasSpoiler) data-spoiler-label="{{ __('editor::rich-text.spoiler') }}" @endif @if($min) data-min="{{ (int) $min }}" @endif @if($max) data-max="{{ (int) $max }}" @endif></div>
    </div>
    <textarea class="hidden" name="{{ $name }}" id="quill-editor-area-{{ $id }}">{!! old($name, $defaultValue) !!}</textarea>
    <div class="mt-2 text-xs text-right " id="quill-counter-wrap-{{ $id }}">
        <span id="quill-counter-{{ $id }}">0</span>
        @if($max)
            <span>/ {{ $max }}</span>
        @endif
        <span id="quill-unit-{{ $id }}"
              data-singular="{{ trans_choice('editor::rich-text.character', 1) }}"
              data-plural="{{ trans_choice('editor::rich-text.character', 2) }}">
            {{ trans_choice('editor::rich-text.character', 2) }}
        </span>
        @if($min)
            <span>({{ trans_choice('editor::rich-text.min-characters', (int) $min, ['min' => (int) $min]) }})</span>
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
