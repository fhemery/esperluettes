<div
    x-data="quotePanel()"
    x-init="$watch('open', v => v && $nextTick(() => $refs.dialog.focus()))"
    x-show="open"
    x-cloak
    data-error-save="{{ __('quote::ui.errors.save_note') }}"
    data-error-delete="{{ __('quote::ui.errors.delete_quote') }}"
    @quote:open-panel.window="showPanel($event.detail.quote)"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/30"
    @click.self="close()"
>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="quote-panel-title"
        x-ref="dialog"
        tabindex="-1"
        class="bg-white rounded-t-xl sm:rounded-xl shadow-xl w-full sm:max-w-md mx-0 sm:mx-4 p-6 outline-none"
        @click.stop
    >
        <div class="flex items-start justify-between mb-4">
            <h2 id="quote-panel-title" class="text-lg font-semibold">{{ __('quote::ui.panel.title') }}</h2>
            <button type="button" @click="close()" aria-label="{{ __('quote::ui.panel.close') }}"
                class="text-gray-400 hover:text-gray-600 leading-none ml-2">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <template x-if="quote">
            <div>
                <blockquote
                    class="border-l-4 border-tertiary/40 pl-3 mb-4 text-sm text-gray-700 italic line-clamp-4"
                    x-text="quote.highlighted_text"
                ></blockquote>

                <template x-if="!editingNote">
                    <div>
                        <template x-if="quote.note">
                            <div class="prose prose-sm mb-4 text-gray-800" x-html="quote.note"></div>
                        </template>
                        <template x-if="!quote.note">
                            <p class="text-sm text-gray-400 mb-4">{{ __('quote::ui.panel.no_note') }}</p>
                        </template>
                        <div class="flex justify-end gap-2">
                            <template x-if="quote.can_delete">
                                <button
                                    type="button"
                                    @click="confirmDelete()"
                                    :disabled="deleting"
                                    class="px-3 py-1.5 text-sm rounded border border-red-300 text-red-600 hover:bg-red-50 disabled:opacity-50"
                                >
                                    <span x-show="!deleting">{{ __('quote::ui.panel.delete') }}</span>
                                    <span x-show="deleting">…</span>
                                </button>
                            </template>
                            <template x-if="quote.can_edit_note">
                                <button
                                    type="button"
                                    @click="startEdit()"
                                    class="px-3 py-1.5 text-sm rounded bg-tertiary text-white hover:bg-tertiary/90"
                                >
                                    {{ __('quote::ui.panel.edit_note') }}
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="editingNote">
                    <div>
                        <label for="quote-panel-note-input" class="sr-only">{{ __('quote::ui.mini_form.note_label') }}</label>
                        <textarea
                            id="quote-panel-note-input"
                            x-model="noteValue"
                            rows="4"
                            maxlength="1000"
                            @keydown.enter="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); saveNote() }"
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:ring-tertiary mb-3"
                            placeholder="{{ __('quote::ui.mini_form.note_placeholder') }}"
                        ></textarea>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="cancelEdit()"
                                class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50"
                            >
                                {{ __('quote::ui.mini_form.cancel') }}
                            </button>
                            <button
                                type="button"
                                @click="saveNote()"
                                :disabled="saving"
                                class="px-4 py-2 text-sm rounded bg-tertiary text-white hover:bg-tertiary/90 disabled:opacity-50"
                            >
                                <span x-show="!saving">{{ __('quote::ui.mini_form.save') }}</span>
                                <span x-show="saving">{{ __('quote::ui.mini_form.saving') }}</span>
                            </button>
                        </div>
                    </div>
                </template>

                <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>
            </div>
        </template>
    </div>
</div>
