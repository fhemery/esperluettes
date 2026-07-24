@props(['entityType', 'entityId', 'canAnnotate' => false, 'viewerRole' => null])
<div
    class="annotable-region"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ $entityId }}"
    data-can-annotate="{{ $canAnnotate ? 'true' : 'false' }}"
    @if($viewerRole) data-viewer-role="{{ $viewerRole }}" @endif
    x-data
>
    <template id="comment-toolbar-template">
        <div class="comment-toolbar bg-white border border-gray-200 rounded-lg shadow-md px-2 py-1" role="toolbar" aria-label="{{ __('quote::ui.toolbar.label') }}">
            {{ $toolbarActions ?? '' }}
        </div>
    </template>

    {{ $slot }}
</div>

@once
    @push('scripts')
        @vite('app/Domains/Comment/Resources/js/annotable/toolbar.js')
    @endpush
@endonce
