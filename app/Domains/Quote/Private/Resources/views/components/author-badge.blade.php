@if($canView)
<div class="flex items-center gap-1"
     data-quote-author-badge="{{ $count }}"
     x-data
     x-init="$store.quoteAggregate.totalCount = {{ $count }}">

    <x-shared::popover placement="top" maxWidth="16rem">
        <x-slot name="trigger">
            <x-shared::badge color="neutral" size="xs" icon="format_quote">
                {{ $count }}
            </x-shared::badge>
        </x-slot>
        <div class="font-semibold text-gray-900 text-center">
            {{ trans_choice('quote::ui.author_badge.label', $count) }}
        </div>
        <div class="text-gray-700 text-center">{{ __('quote::ui.author_badge.tooltip') }}</div>
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
