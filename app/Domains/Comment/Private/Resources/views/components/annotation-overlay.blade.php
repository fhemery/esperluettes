@if($inChapterRole)
<div class="relative"
     data-annotation-overlay
     x-data="annotationOverlay"
     data-role="{{ $inChapterRole }}"
     data-mode="{{ $annotationMode }}"
     @if($rootCommentId) data-root-comment-id="{{ $rootCommentId }}" @endif
     data-entity-type="{{ $entityType }}"
     data-entity-id="{{ $entityId }}"
     data-user-id="{{ $userId }}"
     data-url="{{ $inEntityUrl }}">

    {{ $slot }}

    {{-- Same geometry as Quote's heat gutter; below md it is display:none. --}}
    <div x-ref="gutter"
         class="hidden md:block absolute inset-y-0 right-0 w-6 translate-x-full pointer-events-none"></div>
</div>

{{-- head-scripts, not scripts: the entry registers Alpine.data on alpine:init,
     which app.js (loaded right after head-scripts) fires on Alpine.start(). --}}
@pushOnce('head-scripts', 'comment-annotation-overlay')
    @vite('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js')
@endPushOnce
@else
{{ $slot }}
@endif
