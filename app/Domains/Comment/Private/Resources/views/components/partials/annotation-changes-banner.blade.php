{{-- Save banner for pending changes to the annotations of an already-posted
     root comment (comment-draft `annotationChanges` slot). Outside the root
     form: its buttons never submit it. Hidden until the slot is non-empty and
     the page's [data-annotable] carries data-root-comment-id. --}}
<div
    x-data="annotationChangesBanner"
    x-show="count > 0"
    x-cloak
    data-annotation-changes-banner
    data-testid="annotation-changes-banner"
    data-user-id="{{ (int) Auth::id() }}"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ (int) $entityId }}"
    data-label-one="{{ trans_choice('comment::annotations.changes_banner.text', 1, ['count' => 1]) }}"
    data-label-many="{{ trans_choice('comment::annotations.changes_banner.text', 2, ['count' => '__COUNT__']) }}"
    data-count-one="{{ trans_choice('comment::annotations.button', 1, ['count' => 1]) }}"
    data-count-many="{{ trans_choice('comment::annotations.button', 2, ['count' => '__COUNT__']) }}"
    data-discard-confirm="{{ __('comment::annotations.changes_banner.discard_confirm') }}"
    data-error-items="{{ __('comment::annotations.changes_banner.error_items') }}"
    data-error-generic="{{ __('comment::annotations.changes_banner.error_generic') }}"
    data-stale="{{ __('comment::annotations.errors.stale') }}"
    data-item-edit="{{ __('comment::annotations.changes_banner.item_edit') }}"
    data-item-delete="{{ __('comment::annotations.changes_banner.item_delete') }}"
    role="status"
    aria-live="polite"
    class="sticky bottom-0 z-30 p-3 sm:px-4 rounded-t border border-primary/30 bg-bg shadow-md text-sm"
>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base leading-none" aria-hidden="true">edit_note</span>
            <span x-text="label"></span>
        </span>
        <div class="flex flex-wrap gap-2">
            <x-shared::button type="button" size="sm" color="primary" x-on:click="save()" x-bind:disabled="saving">
                <span x-show="!saving">{{ __('comment::annotations.changes_banner.save') }}</span>
                <span x-show="saving" x-cloak>{{ __('comment::annotations.changes_banner.saving') }}</span>
            </x-shared::button>
            <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="show()">
                {{ __('comment::annotations.changes_banner.show') }}
            </x-shared::button>
            <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="discardAll()" x-bind:disabled="saving">
                {{ __('comment::annotations.changes_banner.discard') }}
            </x-shared::button>
        </div>
    </div>
    <div x-show="error" x-cloak class="mt-2 text-red-600" role="alert">
        <p x-text="error"></p>
        <ul class="list-disc pl-5" x-show="errorLines.length > 0">
            <template x-for="line in errorLines" :key="line">
                <li x-text="line"></li>
            </template>
        </ul>
    </div>
</div>
