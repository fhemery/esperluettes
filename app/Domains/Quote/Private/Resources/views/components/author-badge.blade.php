@if($canView)
<div class="flex items-center gap-1"
     data-quote-author-badge="{{ $count }}"
     x-data
     x-init="$store.quoteAggregate.totalCount = {{ $count }}">

    <x-shared::popover placement="top" maxWidth="20rem">
        <x-slot name="trigger">
            <x-shared::badge color="neutral" size="xs" icon="format_quote">
                {{ $count }}
            </x-shared::badge>
        </x-slot>
        <div class="font-semibold text-gray-900 text-center">
            {{ trans_choice('quote::ui.author_badge.label', $count) }}
        </div>
        <div class="text-gray-700 text-center">{{ __('quote::ui.author_badge.tooltip') }}</div>

        {{-- The chapter summary. It explains the gap the count alone cannot:
             a stale passage is counted here but can never be tinted. --}}
        <div class="mt-2"
             x-data="quoteAuthorSummary({
                chapterId: {{ $chapterId }},
                countLabelOne: {!! e(json_encode($countLabels['one'])) !!},
                countLabelOther: {!! e(json_encode($countLabels['other'])) !!}
             })"
             x-effect="(hoverOpen || pinned) && load()"
             x-show="groups.length"
             x-cloak>
            <div class="font-semibold text-gray-900 border-t border-gray-200 pt-2">
                {{ __('quote::ui.author_summary.title') }}
            </div>
            <ul class="mt-1 flex flex-col gap-1" data-quote-author-summary>
                <template x-for="group in groups" :key="group.key">
                    <li>
                        <template x-if="!group.stale">
                            <button type="button"
                                    class="w-full flex items-start gap-2 text-left rounded px-1 py-0.5 hover:bg-gray-100"
                                    @click="select(group)">
                                <span class="grow line-clamp-2 italic" x-text="group.text"></span>
                                <span class="shrink-0 font-bold text-gray-900"
                                      :aria-label="countLabel(group.count)"
                                      x-text="group.count"></span>
                            </button>
                        </template>
                        <template x-if="group.stale">
                            <div class="flex items-start gap-2 px-1 py-0.5 text-gray-500">
                                <span class="grow line-clamp-2 italic">
                                    <span x-text="group.text"></span>
                                    <span class="not-italic">— {{ __('quote::ui.profile_tab.passage_missing') }}</span>
                                </span>
                                <span class="shrink-0 font-bold"
                                      :aria-label="countLabel(group.count)"
                                      x-text="group.count"></span>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </div>
    </x-shared::popover>

    <button type="button"
            class="inline-flex items-center leading-none text-accent hover:text-accent/80"
            :class="$store.quoteAggregate.visible || 'opacity-60'"
            :aria-pressed="$store.quoteAggregate.visible ? 'true' : 'false'"
            aria-label="{{ __('quote::ui.author_badge.toggle') }}"
            title="{{ __('quote::ui.author_badge.toggle') }}"
            @click="$store.quoteAggregate.toggle({{ $chapterId }})">
        <span class="material-symbols-outlined text-base">format_ink_highlighter</span>
    </button>
</div>
@endif
