@if($inChapterRole)
@php
    // {name} / {count} are filled in per gutter group by the overlay script.
    $avatarLabels = collect(['zero' => 0, 'one' => 1, 'other' => 2])->map(fn (int $n) => trans_choice(
        'comment::annotations.overlay.avatar_label', $n, ['name' => '{name}', 'count' => '{count}']
    ))->all();
@endphp
<div class="relative"
     data-annotation-overlay
     x-data="annotationOverlay"
     data-role="{{ $inChapterRole }}"
     data-mode="{{ $annotationMode }}"
     @if($rootCommentId) data-root-comment-id="{{ $rootCommentId }}" @endif
     data-entity-type="{{ $entityType }}"
     data-entity-id="{{ $entityId }}"
     data-user-id="{{ $userId }}"
     data-user-name="{{ $userName }}"
     data-user-avatar="{{ $userAvatar }}"
     data-default-avatar="{{ asset('images/default-avatar.svg') }}"
     data-avatar-labels="{{ json_encode($avatarLabels) }}"
     data-more-label="{{ __('comment::annotations.overlay.more', ['count' => '{count}']) }}"
     data-url="{{ $inEntityUrl }}">

    {{ $slot }}

    {{-- Same geometry as Quote's heat gutter; below md it is display:none.
         Hidden while the author's heat map is on (decision #2). --}}
    <div x-ref="gutter"
         x-show="!$store.annotationOverlay.heatOn"
         class="hidden md:block absolute inset-y-0 right-0 w-6 translate-x-full pointer-events-none">
        <template x-for="group in groups" :key="group.key">
            <button type="button"
                    class="absolute right-0 pointer-events-auto h-6 w-6 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :style="`top: ${group.top}px`"
                    :aria-label="group.label"
                    :data-annotation-group="group.key"
                    @click="$dispatch('annotation-overlay:open', { groupKey: group.key })">
                <img :src="group.avatar" alt="" class="h-6 w-6 rounded-full object-cover"
                     @@error="if ($el.src !== defaultAvatar) $el.src = defaultAvatar">
                <span x-show="group.more > 0"
                      x-text="group.moreLabel"
                      aria-hidden="true"
                      class="absolute -bottom-1 -right-1 min-w-4 h-4 px-0.5 rounded-full text-[10px] leading-4 font-bold text-center surface-accent text-on-surface"></span>
            </button>
        </template>
    </div>
</div>

{{-- head-scripts, not scripts: the entry registers Alpine.data on alpine:init,
     which app.js (loaded right after head-scripts) fires on Alpine.start(). --}}
@pushOnce('head-scripts', 'comment-annotation-overlay')
    @vite('app/Domains/Comment/Resources/js/annotations/in-chapter/index.js')
@endPushOnce
@else
{{ $slot }}
@endif
