@props(['entityType', 'entityId'])
@inject('commentPolicies', 'App\Domains\Comment\Public\Api\CommentPolicyRegistry')
@php
    $bodyMax = $commentPolicies->getAnnotationBodyMaxLength($entityType);
    $highlightMax = $commentPolicies->getAnnotationHighlightMaxLength($entityType);
@endphp
{{-- Rendered by the consumer outside the annotable region (the toolbar's
     <template> is cloned, so it cannot host this form). Opened by
     <x-comment::annotate-button> (`annotation:open-form`) or to edit a draft
     (`annotation:open-edit`, detail `{ tempId }`). --}}
<template x-teleport="body">
<div
    x-data="annotationForm()"
    x-show="open"
    x-cloak
    @annotation:open-form.window="openForm()"
    @annotation:open-edit.window="openEdit($event.detail)"
    @keydown.escape.window="open && cancel()"
    @keydown="onKeydown($event)"
    @click.outside="open && cancel()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="annotation-form-title"
    data-annotation-form
    data-user-id="{{ (int) Auth::id() }}"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ (int) $entityId }}"
    data-body-max-length="{{ (int) $bodyMax }}"
    data-highlight-max-length="{{ (int) $highlightMax }}"
    data-error-blank="{{ __('comment::annotations.errors.body_blank') }}"
    data-error-body-too-long="{{ __('comment::annotations.errors.body_too_long', ['max' => (int) $bodyMax]) }}"
    data-error-highlight-too-long="{{ __('comment::annotations.errors.highlight_too_long', ['max' => (int) $highlightMax]) }}"
    data-error-highlight-multi-block="{{ __('comment::annotations.errors.highlight_multi_block') }}"
    :style="formStyle"
    class="bg-white rounded-lg shadow-xl p-4 outline-none"
>
    <h2 id="annotation-form-title" class="text-lg font-semibold mb-3">{{ __('comment::annotations.form.title') }}</h2>

    <blockquote class="border-l-4 border-primary/40 pl-3 mb-3 text-sm text-gray-700 line-clamp-3 whitespace-pre-line"
                x-text="highlighted">
    </blockquote>

    <span class="sr-only">{{ __('comment::annotations.form.body_label') }}</span>
    <x-editor::rich-text
        id="annotation-body-editor"
        name="annotation_body"
        toolbar="inline"
        :max="$bodyMax"
        :nbLines="5"
        :resizable="false"
        isMandatory="true"
    />

    <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>

    <div class="mt-3 flex justify-end gap-2">
        <button
            type="button"
            @click="cancel()"
            class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50"
        >
            {{ __('comment::annotations.form.cancel') }}
        </button>
        <button
            type="button"
            @click="save()"
            :disabled="!canSave"
            class="px-4 py-2 text-sm rounded bg-primary text-white hover:bg-primary/90 disabled:opacity-50"
        >
            {{ __('comment::annotations.form.save') }}
        </button>
    </div>
</div>
</template>

@pushOnce('head-scripts', 'comment-annotations-bundle')
    @vite('app/Domains/Comment/Resources/js/annotations/index.js')
@endPushOnce
