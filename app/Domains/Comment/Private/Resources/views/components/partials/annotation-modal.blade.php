{{-- Server-mode pop-up: published annotations under one root comment, opened by
     the « N annotations » button of comment-item (window event annotations:open).
     One instance per comment list, outside the root-comment form. Highlighted
     text is plain text (x-text); bodies are sanitized server-side (x-html).
     Row actions follow the per-row flags of GET /comments/{id}/annotations.
     For the root's writer, rows carry the pending state of the comment-draft
     annotationChanges slot and pending adds follow them; their bodies come from
     the writer's own Quill editor and are rendered like the v1 drafts modal.
     Each root shows its reply thread (bodies sanitized server-side); the single
     reply editor rests hidden at the bottom and is moved under the row being
     answered by annotationsModal.startReply(). --}}
@inject('annotationPolicies', 'App\Domains\Comment\Public\Api\CommentPolicyRegistry')
@php
    $replyMax = $annotationPolicies->getAnnotationBodyMaxLength($entityType);
@endphp
<div
    x-data="annotationsModal()"
    x-on:annotations:open.window="open($event.detail.commentId)"
    data-user-id="{{ (int) Auth::id() }}"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ (int) $entityId }}"
    data-delete-with-replies-confirm="{{ __('comment::annotations.server_modal.delete_with_replies_confirm') }}"
    data-delete-reply-confirm="{{ __('comment::annotations.replies.delete_confirm') }}"
    data-reply-max-length="{{ (int) $replyMax }}"
    data-reply-empty="{{ __('comment::annotations.replies.empty_body') }}"
    data-reply-too-long="{{ __('comment::annotations.replies.too_long', ['max' => (int) $replyMax]) }}"
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
            <p x-show="!loading && !error && rows.length === 0" class="text-sm text-gray-500">
                {{ __('comment::annotations.server_modal.empty') }}
            </p>

            <ul class="space-y-4">
                <template x-for="row in rows" :key="row.id">
                    <li class="border-b border-gray-200 pb-4 last:border-b-0">
                        <div x-show="row.state" data-testid="annotation-pending" class="mb-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                <span class="material-symbols-outlined text-[14px] leading-none" aria-hidden="true">edit_note</span>
                                <span x-show="row.state === 'edited'">{{ __('comment::annotations.server_modal.pending_edited') }}</span>
                                <span x-show="row.state === 'deleted'">{{ __('comment::annotations.server_modal.pending_deleted') }}</span>
                                <span x-show="row.state === 'added'">{{ __('comment::annotations.server_modal.pending_added') }}</span>
                            </span>
                        </div>
                        <div x-show="showsProcessed(row)" data-testid="annotation-processed" class="mb-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success">
                                <span class="material-symbols-outlined text-[14px] leading-none" aria-hidden="true">check</span>
                                {{ __('comment::annotations.server_modal.processed') }}
                            </span>
                        </div>
                        <blockquote
                            class="border-l-4 border-primary/40 pl-3 mb-2 text-sm text-gray-700 whitespace-pre-line"
                            x-text="row.highlighted_text"
                        ></blockquote>
                        <div
                            class="comment-body rich-content text-sm"
                            x-bind:class="row.state === 'deleted' && 'line-through opacity-60'"
                            x-html="row.body"
                        ></div>
                        <div x-show="rowError(row)" class="mt-2 flex items-center justify-between gap-2 text-sm text-red-600" role="alert">
                            <span x-text="rowError(row)"></span>
                            <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="undo(row)">
                                {{ __('comment::annotations.server_modal.remove_stale') }}
                            </x-shared::button>
                        </div>
                        <div x-show="canToggle(row) || canRemove(row) || canEdit(row) || (canUndo(row) && !rowError(row))" class="mt-2 flex flex-wrap justify-end gap-2">
                            <template x-if="canUndo(row) && !rowError(row)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="undo(row)">
                                    <span x-show="row.state === 'edited'">{{ __('comment::annotations.server_modal.undo_edit') }}</span>
                                    <span x-show="row.state !== 'edited'">{{ __('comment::annotations.server_modal.undo') }}</span>
                                </x-shared::button>
                            </template>
                            <template x-if="canEdit(row)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="edit(row)">
                                    {{ __('comment::annotations.server_modal.edit') }}
                                </x-shared::button>
                            </template>
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

                        <div class="mt-3 pl-4 sm:pl-6 space-y-3">
                            <ul x-show="(row.replies ?? []).length > 0" class="space-y-3">
                                <template x-for="reply in (row.replies ?? [])" :key="reply.id">
                                    <li class="border-l-2 border-gray-200 pl-3" data-testid="annotation-reply">
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <img
                                                x-bind:src="reply.author_profile?.avatar_url || '{{ asset('images/default-avatar.svg') }}'"
                                                alt=""
                                                class="h-6 w-6 rounded-full object-cover"
                                            />
                                            <span class="font-medium text-gray-700" x-text="replyAuthor(reply)"></span>
                                            <time x-bind:datetime="reply.created_at" x-text="replyDate(reply)"></time>
                                        </div>
                                        <div class="comment-body rich-content mt-1 text-sm" x-html="reply.body"></div>
                                        <template x-if="canDeleteReply(reply)">
                                            <div class="mt-1 flex justify-end">
                                                <x-shared::button type="button" size="sm" color="danger" x-on:click="removeReply(reply)" x-bind:disabled="busyId !== null">
                                                    {{ __('comment::annotations.replies.delete') }}
                                                </x-shared::button>
                                            </div>
                                        </template>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="showsHint(row)" class="text-sm text-gray-600" role="status">
                                {{ __('comment::annotations.replies.author_hint') }}
                            </p>
                            <div x-bind:data-reply-slot="row.id"></div>
                            <template x-if="canReply(row) && replyingTo !== row.id">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="startReply(row)">
                                        {{ __('comment::annotations.replies.reply') }}
                                    </x-shared::button>
                                </div>
                            </template>
                        </div>
                    </li>
                </template>
            </ul>

            {{-- Resting place of the single reply editor; startReply() moves it under a row. --}}
            <div hidden>
                <div data-reply-editor class="mt-2">
                    <span class="sr-only">{{ __('comment::annotations.replies.body_label') }}</span>
                    <x-editor::rich-text
                        id="annotation-reply-editor"
                        name="annotation_reply_body"
                        toolbar="inline"
                        :max="$replyMax"
                        :nbLines="3"
                        :resizable="false"
                        isMandatory="true"
                    />
                    <p x-show="replyError" x-text="replyError" class="mt-2 text-sm text-red-600" role="alert"></p>
                    <div class="mt-2 flex flex-wrap justify-end gap-2">
                        <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="cancelReply()">
                            {{ __('comment::annotations.replies.cancel') }}
                        </x-shared::button>
                        <x-shared::button type="button" size="sm" color="accent" x-on:click="sendReply()" x-bind:disabled="!replyValid || sendingReply">
                            {{ __('comment::annotations.replies.send') }}
                        </x-shared::button>
                    </div>
                </div>
            </div>
        </div>
    </x-shared::modal>
</div>
