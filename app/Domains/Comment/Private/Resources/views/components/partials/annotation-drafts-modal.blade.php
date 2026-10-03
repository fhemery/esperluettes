{{-- Drafts-mode pop-up. Lives inside the annotationDrafts scope (and inside the
     root-comment form: every button is type="button"). Bodies are the reader's
     own Quill HTML from localStorage; the server sanitizes them on post. --}}
<x-shared::modal name="annotation-drafts" maxWidth="2xl">
    <div class="p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="annotation-drafts-title">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 id="annotation-drafts-title" class="text-lg font-semibold">
                {{ __('comment::annotations.drafts_modal.title') }}
            </h2>
            <button
                type="button"
                class="text-gray-500 hover:text-gray-700"
                x-on:click="show = false"
                aria-label="{{ __('comment::annotations.drafts_modal.close') }}"
            >
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <p x-show="drafts.length === 0" class="text-sm text-gray-500">
            {{ __('comment::annotations.drafts_modal.empty') }}
        </p>

        <ul class="space-y-4">
            <template x-for="draft in drafts" :key="draft.tempId">
                <li class="border-b border-gray-200 pb-4 last:border-b-0">
                    <blockquote
                        class="border-l-4 border-primary/40 pl-3 mb-2 text-sm text-gray-700 whitespace-pre-line"
                        x-text="draft.highlighted"
                    ></blockquote>
                    <div class="comment-body text-sm" x-html="draft.body"></div>
                    <div class="mt-2 flex justify-end gap-2">
                        <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="edit(draft.tempId)">
                            {{ __('comment::annotations.drafts_modal.edit') }}
                        </x-shared::button>
                        <x-shared::button type="button" size="sm" color="danger" x-on:click="remove(draft.tempId)">
                            {{ __('comment::annotations.drafts_modal.delete') }}
                        </x-shared::button>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</x-shared::modal>
