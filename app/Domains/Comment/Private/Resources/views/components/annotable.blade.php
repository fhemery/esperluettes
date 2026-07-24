@props(['entityType', 'entityId', 'canAnnotate' => false, 'viewerRole' => null, 'maxSelection' => 500])
<div
    class="annotable-region"
    data-annotable
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ $entityId }}"
    data-can-annotate="{{ $canAnnotate ? 'true' : 'false' }}"
    data-max-selection="{{ (int) $maxSelection }}"
    @if($viewerRole) data-viewer-role="{{ $viewerRole }}" @endif
    x-data
>
    <template id="comment-toolbar-template">
        <div class="comment-toolbar bg-white border border-gray-200 rounded-lg shadow-md px-2 py-1" role="toolbar" aria-label="{{ __('comment::annotable.toolbar.label') }}">
            <div data-toolbar-actions class="flex items-center gap-1">
                {{ $toolbarActions ?? '' }}
            </div>
            <span data-toolbar-too-long class="hidden px-2 py-1 text-sm text-red-600">{{ __('comment::annotable.toolbar.too_long') }}</span>
        </div>
    </template>

    {{ $slot }}
</div>

@once
    @push('scripts')
        @vite('app/Domains/Comment/Resources/js/annotable/toolbar.js')
    @endpush
@endonce
