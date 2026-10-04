@props(['entityType', 'canAnnotate' => false])
@if ($canAnnotate)
@inject('commentPolicies', 'App\Domains\Comment\Public\Api\CommentPolicyRegistry')
{{-- Rendered inside the selection toolbar's <template> and cloned on each
     selection, beside <x-comment::annotate-button>. A click stores the emoji as
     an annotation on the selection — a draft or a pending add, per the
     annotable's data-annotation-mode — without opening a form. Same annotatable
     area rule as « Annoter » (ANNOTATABLE_AREA_SELECTOR in
     Resources/js/annotations/capture-form.js) — keep them in sync. --}}
<div
    class="inline-flex items-center gap-1"
    x-data="annotationReactions()"
    data-annotation-reactions
    data-user-id="{{ (int) Auth::id() }}"
    data-highlight-max-length="{{ (int) $commentPolicies->getAnnotationHighlightMaxLength($entityType) }}"
>
    @foreach (['heart' => '❤️', 'fire' => '🔥', 'thumbs_up' => '👍'] as $key => $emoji)
        <button
            type="button"
            class="inline-flex items-center px-2 py-1 text-base leading-none rounded
                   hover:bg-primary/10 border border-transparent hover:border-primary/30 transition-colors"
            data-requires-selection-within=".ce-block--text"
            data-requires-single-area
            x-on:click="react('{{ $emoji }}')"
            aria-label="{{ __('comment::annotations.reactions.' . $key) }}"
            title="{{ __('comment::annotations.reactions.' . $key) }}"
        >{{ $emoji }}</button>
    @endforeach
</div>

@pushOnce('head-scripts', 'comment-annotations-bundle')
    @vite('app/Domains/Comment/Resources/js/annotations/index.js')
@endPushOnce
@endif
