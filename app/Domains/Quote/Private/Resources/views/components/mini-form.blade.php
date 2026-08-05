<template x-teleport="body">
<div
    x-data="quoteMiniForm()"
    x-init="$watch('open', v => v && $nextTick(() => $refs.dialog.focus()))"
    x-show="open"
    x-cloak
    @quote:open-mini-form.window="openForm($event.detail)"
    @keydown.escape.window="cancel()"
    @click.outside="cancel()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="quote-mini-form-title"
    x-ref="dialog"
    tabindex="-1"
    data-error-save="{{ __('quote::ui.errors.save_quote') }}"
    data-highlight-max-length="{{ config('quote.highlighted_text_max_length') }}"
    data-error-highlight-too-long="{{ __('quote::ui.errors.highlight_too_long', ['max' => config('quote.highlighted_text_max_length')]) }}"
    data-error-highlight-multi-block="{{ __('quote::ui.errors.highlight_multi_block') }}"
    :style="`position:absolute; top:${_pos.top}px; left:${_pos.left}px; width:360px; z-index:9999;`"
    class="bg-white rounded-lg shadow-xl p-6 outline-none"
>
    <h2 id="quote-mini-form-title" class="text-lg font-semibold mb-3">{{ __('quote::ui.mini_form.title') }}</h2>

    <blockquote class="border-l-4 border-tertiary/40 pl-3 mb-4 text-sm text-gray-700 italic line-clamp-3 whitespace-pre-line"
                x-text="selectedText">
    </blockquote>

    <div class="mb-4">
        <label for="quote-note-input" class="block text-sm font-medium text-gray-700 mb-1">
            {{ __('quote::ui.mini_form.note_label') }}
        </label>
        <textarea
            id="quote-note-input"
            x-model="note"
            rows="3"
            maxlength="1000"
            @keydown.enter="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); save() }"
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
            :disabled="saving || tooLong || multiBlock"
            class="px-4 py-2 text-sm rounded bg-tertiary text-white hover:bg-tertiary/90 disabled:opacity-50"
        >
            <span x-show="!saving">{{ __('quote::ui.mini_form.save') }}</span>
            <span x-show="saving">{{ __('quote::ui.mini_form.saving') }}</span>
        </button>
    </div>

    <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600"></p>
</div>
</template>

@once
    @push('head-scripts')
        @vite('app/Domains/Quote/Resources/js/quote/index.js')
    @endpush
@endonce
