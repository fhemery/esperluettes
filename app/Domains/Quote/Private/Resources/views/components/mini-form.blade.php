<div
    x-data="quoteMiniForm()"
    x-show="open"
    x-cloak
    @quote:open-mini-form.window="openForm($event.detail)"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
    @keydown.escape.window="cancel()"
>
    <div
        class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6"
        @click.stop
    >
        <h2 class="text-lg font-semibold mb-3">{{ __('quote::ui.mini_form.title') }}</h2>

        <blockquote class="border-l-4 border-tertiary/40 pl-3 mb-4 text-sm text-gray-700 italic line-clamp-3"
                    x-text="selectedText">
        </blockquote>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('quote::ui.mini_form.note_label') }}
            </label>
            <textarea
                x-model="note"
                rows="3"
                maxlength="1000"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:ring-tertiary"
                placeholder="{{ __('quote::ui.mini_form.note_placeholder') }}"
            ></textarea>
        </div>

        <div class="flex justify-end gap-2">
            <button
                type="button"
                @click="cancel()"
                class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50"
            >
                {{ __('quote::ui.mini_form.cancel') }}
            </button>
            <button
                type="button"
                @click="save()"
                :disabled="saving"
                class="px-4 py-2 text-sm rounded bg-tertiary text-white hover:bg-tertiary/90 disabled:opacity-50"
            >
                <span x-show="!saving">{{ __('quote::ui.mini_form.save') }}</span>
                <span x-show="saving">{{ __('quote::ui.mini_form.saving') }}</span>
            </button>
        </div>

        <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>
    </div>
</div>
