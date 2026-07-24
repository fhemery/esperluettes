@props(['chapterId', 'storyId', 'canQuote' => false])
@if ($canQuote)
<button
    type="button"
    class="quote-toolbar-btn inline-flex items-center gap-1 px-3 py-1 text-sm
           rounded bg-tertiary/10 hover:bg-tertiary/20 text-tertiary-800
           border border-tertiary/30 transition-colors"
    x-on:click="$dispatch('quote:open-mini-form', {
        chapterId: {{ $chapterId }},
        storyId: {{ $storyId }},
        toolbar: $el.closest('[id]')
    })"
    title="{{ __('quote::ui.toolbar_button.title') }}"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    <span>{{ __('quote::ui.toolbar_button.label') }}</span>
</button>
@endif

@once
    @push('head-scripts')
        @vite('app/Domains/Quote/Resources/js/quote/index.js')
    @endpush
@endonce
