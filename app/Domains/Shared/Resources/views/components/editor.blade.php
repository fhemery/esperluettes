@props(['name', 'id', 'defaultValue' => '', 'max' => null, 'min' => null, 'nbLines' => 5, 'placeholder' => '', 'isMandatory' => false])
<div {{ $attributes->merge(['class' => 'rich-content']) }}>
    <div class="mb-2" id="{{ $id }}" data-placeholder="{{ $placeholder }}" data-nb-lines="{{ $nbLines }}" data-is-mandatory="{{ $isMandatory ? 'true' : 'false' }}" data-clean-label="{{ __('shared::editor.clean') }}" @if($min) data-min="{{ (int) $min }}" @endif @if($max) data-max="{{ (int) $max }}" @endif></div>
    <input type="hidden" name="{{ $name }}" id="quill-editor-area-{{ $id }}" value="{!! $defaultValue !!}" />
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