@props(['canAnnotate' => false])
@if ($canAnnotate)
{{-- Rendered inside the selection toolbar's <template> and cloned on each
     selection: it only signals the capture form (<x-comment::annotation-form>).
     Annotatable area rule, also written as ANNOTATABLE_AREA_SELECTOR in
     Resources/js/annotations/capture-form.js — keep both in sync. --}}
<button
    type="button"
    class="annotation-toolbar-btn inline-flex items-center gap-1 px-3 py-1 text-sm
           rounded bg-primary/10 hover:bg-primary/20 text-primary
           border border-primary/30 transition-colors"
    data-requires-selection-within=".ce-block--text"
    x-on:click="window.dispatchEvent(new CustomEvent('annotation:open-form'))"
    title="{{ __('comment::annotations.toolbar_button.title') }}"
>
    <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">edit_note</span>
    <span>{{ __('comment::annotations.toolbar_button.label') }}</span>
</button>

@pushOnce('head-scripts', 'comment-annotations-bundle')
    @vite('app/Domains/Comment/Resources/js/annotations/index.js')
@endPushOnce
@endif
