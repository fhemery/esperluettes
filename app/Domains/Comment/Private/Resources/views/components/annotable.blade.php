<div
    class="annotable-region"
    data-entity-type="{{ $entityType }}"
    data-entity-id="{{ $entityId }}"
    data-can-annotate="{{ $canAnnotate ? 'true' : 'false' }}"
    @if($viewerRole) data-viewer-role="{{ $viewerRole }}" @endif
    x-data
>
    <template id="comment-toolbar-template">
        <div class="comment-toolbar" role="toolbar" aria-label="{{ __('quote::toolbar.label') }}">
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
