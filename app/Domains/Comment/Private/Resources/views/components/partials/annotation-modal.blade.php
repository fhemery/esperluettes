{{-- Server-mode pop-up: published annotations under one root comment, opened by
     the « N annotations » button of comment-item (window event annotations:open).
     One instance per comment list, outside the root-comment form. Highlighted
     text is plain text (x-text); bodies are sanitized server-side (x-html).
     Row actions follow the per-row flags of GET /comments/{id}/annotations. --}}
<div
    x-data="annotationsModal()"
    x-on:annotations:open.window="open($event.detail.commentId)"
    data-label-one="{{ trans_choice('comment::annotations.button', 1, ['count' => 1]) }}"
    data-label-many="{{ trans_choice('comment::annotations.button', 2, ['count' => '__COUNT__']) }}"
    data-load-error="{{ __('comment::annotations.server_modal.load_error') }}"
    data-action-error="{{ __('comment::annotations.server_modal.action_error') }}"
>
    <x-shared::modal name="annotations-server" maxWidth="2xl">
        <div class="p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="annotations-server-title">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 id="annotations-server-title" class="text-lg font-semibold">
                    {{ __('comment::annotations.server_modal.title') }}
                </h2>
                <button
                    type="button"
                    class="text-gray-500 hover:text-gray-700"
                    x-on:click="show = false"
                    aria-label="{{ __('comment::annotations.server_modal.close') }}"
                >
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>

            <p x-show="loading" class="text-sm text-gray-500">
                {{ __('comment::annotations.server_modal.loading') }}
            </p>
            <p x-show="error" x-text="error" class="mb-3 text-sm text-red-600" role="alert"></p>
            <p x-show="!loading && !error && items.length === 0" class="text-sm text-gray-500">
                {{ __('comment::annotations.server_modal.empty') }}
            </p>

            <ul class="space-y-4">
                <template x-for="row in items" :key="row.id">
                    <li class="border-b border-gray-200 pb-4 last:border-b-0">
                        <div x-show="showsProcessed(row)" class="mb-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success">
                                <span class="material-symbols-outlined text-[14px] leading-none" aria-hidden="true">check</span>
                                {{ __('comment::annotations.server_modal.processed') }}
                            </span>
                        </div>
                        <blockquote
                            class="border-l-4 border-primary/40 pl-3 mb-2 text-sm text-gray-700 whitespace-pre-line"
                            x-text="row.highlighted_text"
                        ></blockquote>
                        <div class="comment-body rich-content text-sm" x-html="row.body"></div>
                        <div x-show="canToggle(row) || canRemove(row)" class="mt-2 flex justify-end gap-2">
                            <template x-if="canToggle(row)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="toggle(row)" x-bind:disabled="busyId !== null">
                                    <span x-show="!row.is_processed">{{ __('comment::annotations.server_modal.mark_processed') }}</span>
                                    <span x-show="row.is_processed">{{ __('comment::annotations.server_modal.mark_unprocessed') }}</span>
                                </x-shared::button>
                            </template>
                            <template x-if="canRemove(row)">
                                <x-shared::button type="button" size="sm" color="danger" x-on:click="remove(row)" x-bind:disabled="busyId !== null">
                                    {{ __('comment::annotations.server_modal.delete') }}
                                </x-shared::button>
                            </template>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </x-shared::modal>
</div>
