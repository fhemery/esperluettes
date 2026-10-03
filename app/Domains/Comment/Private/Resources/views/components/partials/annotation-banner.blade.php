{{-- Annotation drafts banner, inside the root-comment form. Mirrors the
     comment-draft `annotations` slot (annotationDrafts, annotations bundle) and
     fills the hidden `annotations` input on submit. Hosts the drafts pop-up so
     both share one Alpine scope. --}}
@php
    $annotationsError = $errors->first('annotations') ?: $errors->first('annotations.*');
@endphp
<div
    x-data="annotationDrafts()"
    data-annotation-drafts
    data-user-id="{{ (int) Auth::id() }}"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ (int) $entityId }}"
    data-label-one="{{ trans_choice('comment::annotations.banner.text', 1) }}"
    data-label-many="{{ trans_choice('comment::annotations.banner.text', 2, ['count' => '__COUNT__']) }}"
>
    <input type="hidden" name="annotations" value="">

    <div
        x-show="visible"
        x-cloak
        data-testid="annotation-banner"
        class="flex flex-wrap items-center justify-between gap-2 p-3 rounded border border-primary/30 bg-primary/5 text-sm"
    >
        <span class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">edit_note</span>
            <span x-text="label"></span>
        </span>
        <x-shared::button type="button" size="sm" color="primary" x-on:click="openModal()">
            {{ __('comment::annotations.banner.show') }}
        </x-shared::button>
    </div>

    @if($annotationsError)
        <div class="mt-1 text-sm text-red-600">{{ $annotationsError }}</div>
    @endif

    @include('comment::components.partials.annotation-drafts-modal')
</div>
