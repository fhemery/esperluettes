{{--
    The author's passage popover: who quoted the clicked passage, and when.

    Separate from <x-quote::chapter-panel>, which stays the reader's own
    note/edit/delete panel. There is no note here because the aggregate payload
    carries none — the guarantee is server-side, not a condition below.
--}}
@php($titleOne = trans_choice('quote::ui.author_panel.title', 1, ['count' => '{count}']))
@php($titleOther = trans_choice('quote::ui.author_panel.title', 2, ['count' => '{count}']))

<div
    x-data="quoteAuthorPassagePanel({
        titleOne: {!! e(json_encode($titleOne)) !!},
        titleOther: {!! e(json_encode($titleOther)) !!}
    })"
    x-init="$watch('open', v => v && $nextTick(() => $refs.dialog.focus()))"
    x-show="open"
    x-cloak
    @quote:open-author-panel.window="show($event.detail.quotes)"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/30"
    @click.self="close()"
>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="quote-author-panel-title"
        x-ref="dialog"
        tabindex="-1"
        class="surface-read text-on-surface rounded-t-xl sm:rounded-xl shadow-xl w-full sm:max-w-md mx-0 sm:mx-4 p-6 outline-none"
        @click.stop
    >
        <div class="flex items-start justify-between mb-4">
            <h2 id="quote-author-panel-title" class="text-lg font-semibold" x-text="title()"></h2>
            <button type="button" @click="close()" aria-label="{{ __('quote::ui.author_panel.close') }}"
                class="text-fg/50 hover:text-fg leading-none ml-2">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <ul class="flex flex-col gap-3">
            <template x-for="quote in quotes" :key="quote.id">
                <li class="flex items-center gap-3">
                    <x-shared::avatar :src="''" class="h-8 w-8 shrink-0"
                        x-bind:src="quote.quoter.avatar_url" x-bind:alt="quote.quoter.display_name" />
                    <div class="min-w-0">
                        <a :href="profileUrl(quote)" class="hover:underline font-medium block truncate"
                            x-text="quote.quoter.display_name"></a>
                        <span class="text-sm text-fg/60" x-text="relativeDate(quote.created_at)"></span>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</div>
