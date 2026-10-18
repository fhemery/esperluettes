{{-- Listens to `fanart-open` ({ url }) dispatched by thumbnails, group lists and the tab shell. --}}
<div x-data="fanartViewer" x-cloak x-show="isOpen"
     x-on:fanart-open.window="open($event.detail.url)"
     x-on:keydown.escape.window="close()"
     x-on:keydown.tab="trapFocus($event)"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-2 md:p-8"
     role="dialog" aria-modal="true" aria-label="{{ __('fanart::ui.story_page.viewer_label') }}"
     id="fanart-viewer-modal">
    <div class="fixed inset-0 bg-black/70" x-on:click="close()" aria-hidden="true"></div>
    <div x-ref="panel" class="relative w-full max-w-6xl surface-read text-on-surface rounded-lg shadow-xl" tabindex="-1">
        <button type="button" x-ref="closeButton" x-on:click="close()"
                class="absolute top-2 right-2 z-10 p-1 rounded hover:text-accent"
                aria-label="{{ __('fanart::ui.story_page.viewer_close') }}">
            <span class="material-symbols-outlined">close</span>
        </button>
        <div x-html="html"><p class="p-4">{{ __('fanart::ui.tab.loading') }}</p></div>
    </div>
</div>

@once
    @push('head-scripts')
        @vite('app/Domains/Fanart/Private/Resources/js/fanart-viewer.js')
    @endpush
@endonce
