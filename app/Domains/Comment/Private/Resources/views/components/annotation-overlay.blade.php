@if($inChapterRole)
@php
    // {name} / {count} are filled in per gutter group by the overlay script.
    $avatarLabels = collect(['zero' => 0, 'one' => 1, 'other' => 2])->map(fn (int $n) => trans_choice(
        'comment::annotations.overlay.avatar_label', $n, ['name' => '{name}', 'count' => '{count}']
    ))->all();
@endphp
<div class="relative"
     :class="$store.annotationOverlay.shown && 'pr-6 lg:pr-0'"
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
     data-unknown-name="{{ __('comment::comments.unknown_user') }}"
     data-avatar-labels="{{ json_encode($avatarLabels) }}"
     data-more-label="{{ __('comment::annotations.overlay.more', ['count' => '{count}']) }}"
     data-url="{{ $inEntityUrl }}">

    @if($inChapterRole === 'author')
    {{-- Author's filter menu (annotationFilterMenu, filter-menu.js): shown with
         the annotations, when there is at least one. State is per visit. --}}
    <div x-data="annotationFilterMenu"
         x-show="shown"
         style="display:none"
         data-unknown-name="{{ __('comment::comments.unknown_user') }}"
         data-default-avatar="{{ asset('images/default-avatar.svg') }}"
         class="flex justify-end mb-2 relative"
         @click.outside="open = false"
         @keydown.escape="open = false">
        <button type="button"
                class="inline-flex items-center gap-1 text-sm text-fg/70 hover:text-fg rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="annotation-filter-panel">
            <span class="material-symbols-outlined text-[18px] leading-none" aria-hidden="true">filter_list</span>
            {{ __('comment::annotations.filter.title') }}
        </button>
        <div id="annotation-filter-panel"
             x-show="open" x-cloak style="display:none"
             class="absolute right-0 top-full z-40 mt-1 w-64 p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700">
            <fieldset>
                <legend class="font-medium text-gray-900">{{ __('comment::annotations.filter.commenters') }}</legend>
                <ul class="mt-2 space-y-1 max-h-64 overflow-y-auto">
                    <template x-for="commenter in commenters" :key="commenter.id ?? 'anonymised'">
                        <li>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" class="rounded border-gray-300 text-accent focus:ring-accent"
                                       :checked="isTicked(commenter.id)"
                                       @change="toggleCommenter(commenter.id)">
                                <img :src="commenter.avatar" alt="" class="h-5 w-5 rounded-full object-cover"
                                     @@error="if ($el.src !== $root.dataset.defaultAvatar) $el.src = $root.dataset.defaultAvatar">
                                <span class="truncate" x-text="commenter.name"></span>
                            </label>
                        </li>
                    </template>
                </ul>
            </fieldset>
            <label class="mt-3 pt-3 border-t border-gray-200 flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="rounded border-gray-300 text-accent focus:ring-accent"
                       :checked="showProcessed"
                       @change="toggleProcessed()">
                <span>{{ __('comment::annotations.show_processed') }}</span>
            </label>
        </div>
    </div>
    @endif

    {{ $slot }}

    {{-- From lg the gutter hangs in the page's right column (as Quote's heat gutter).
         Below it there is no such column: the root reserves 24px (pr-6, above) while
         annotations are shown and the gutter sits inside it. Hidden while the
         author's heat map is on (decision #2). --}}
    <div x-ref="gutter"
         x-show="!$store.annotationOverlay.heatOn"
         class="absolute inset-y-0 right-0 w-6 lg:translate-x-full pointer-events-none">
        <template x-for="group in groups" :key="group.key">
            <button type="button"
                    class="absolute right-0 pointer-events-auto h-6 w-6 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :style="`top: ${group.top}px`"
                    :aria-label="group.label"
                    :data-annotation-group="group.key"
                    aria-haspopup="dialog"
                    @click="$dispatch('annotation-overlay:open', { groupKey: group.key, keys: group.members })">
                <img :src="group.avatar" alt="" class="h-6 w-6 rounded-full object-cover"
                     :class="{ 'opacity-50': group.processed }"
                     @@error="if ($el.src !== defaultAvatar) $el.src = defaultAvatar">
                <span x-show="group.more > 0"
                      x-text="group.moreLabel"
                      aria-hidden="true"
                      class="absolute -bottom-1 -right-1 min-w-4 h-4 px-0.5 rounded-full text-[10px] leading-4 font-bold text-center surface-accent text-on-surface"></span>
            </button>
        </template>
    </div>

    {{-- Popover on one gutter group (annotationPopover, popover.js). The panel is
         teleported to body; bodies are sanitized server-side (x-html), the
         emptied placeholder included. Actions follow the row's can_* flags. --}}
    <div x-data="annotationPopover"
         data-position="{{ __('comment::annotations.overlay.position', ['index' => '{index}', 'total' => '{total}']) }}"
         data-action-error="{{ __('comment::annotations.server_modal.action_error') }}"
         data-reply-max-length="{{ $replyMax }}"
         data-reply-empty="{{ __('comment::annotations.replies.empty_body') }}"
         data-reply-too-long="{{ __('comment::annotations.replies.too_long', ['max' => $replyMax]) }}"
         data-delete-with-replies="{{ __('comment::annotations.server_modal.delete_with_replies_confirm') }}"
         data-user-name="{{ $userName }}"
         data-user-avatar="{{ $userAvatar }}"
         data-unknown-name="{{ __('comment::comments.unknown_user') }}"
         data-default-avatar="{{ asset('images/default-avatar.svg') }}">
        <template x-teleport="body">
            <div x-cloak x-show="open" style="display:none"
                 data-annotation-popover-panel
                 role="dialog"
                 aria-label="{{ __('comment::annotations.server_modal.title') }}"
                 tabindex="-1"
                 :style="styleObj"
                 x-effect="sync()"
                 class="fixed z-[9999] w-80 p-3 rounded-md shadow-lg bg-white ring-1 ring-black/5 text-sm text-gray-700 focus:outline-none">
                <div class="flex items-center gap-2">
                    <template x-if="current">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <img :src="authorAvatar(current)" alt="" class="h-6 w-6 rounded-full object-cover"
                                 @@error="if ($el.src !== defaultAvatar) $el.src = defaultAvatar">
                            <span class="font-medium text-gray-900 truncate" x-text="authorName(current)"></span>
                        </div>
                    </template>
                    <div x-show="members.length > 1" class="flex items-center gap-1 shrink-0">
                        <button type="button" class="rounded-sm text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                @click="previous()" aria-label="{{ __('comment::annotations.overlay.previous') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none" aria-hidden="true">chevron_left</span>
                        </button>
                        <span class="text-xs tabular-nums" x-text="position" aria-live="polite"></span>
                        <button type="button" class="rounded-sm text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                @click="next()" aria-label="{{ __('comment::annotations.overlay.next') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none" aria-hidden="true">chevron_right</span>
                        </button>
                    </div>
                    <button type="button" class="ml-auto rounded-sm text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                            @click="close(true)" aria-label="{{ __('comment::annotations.overlay.close') }}">
                        <span class="material-symbols-outlined text-[18px] leading-none" aria-hidden="true">close</span>
                    </button>
                </div>

                <template x-if="current">
                    <div>
                        <div class="comment-body rich-content mt-2" x-html="current.body"></div>

                        <ul x-show="(current.replies ?? []).length > 0" class="mt-3 pl-3 space-y-3">
                            <template x-for="reply in (current.replies ?? [])" :key="reply.id">
                                <li class="border-l-2 border-gray-200 pl-3" data-testid="annotation-popover-reply">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <img :src="replyAvatar(reply)" alt="" class="h-5 w-5 rounded-full object-cover"
                                             @@error="if ($el.src !== defaultAvatar) $el.src = defaultAvatar">
                                        <span class="font-medium text-gray-700" x-text="replyAuthor(reply)"></span>
                                        <time :datetime="reply.created_at" x-text="replyDate(reply)"></time>
                                    </div>
                                    <div class="comment-body rich-content mt-1" x-html="reply.body"></div>
                                    <template x-if="reply.can_report">
                                        <div class="mt-1 flex justify-end">
                                            <div x-data="reportButton('annotation', reply.id)">
                                                <x-shared::button type="button" size="sm" color="error" data-testid="annotation-popover-report" title="{{ __('moderation::report.button') }}" aria-label="{{ __('moderation::report.button') }}" x-on:click="loadForm()" x-bind:disabled="loading">
                                                    <span class="material-symbols-outlined" aria-hidden="true">flag</span>
                                                </x-shared::button>
                                                <div x-ref="modalContainer"></div>
                                            </div>
                                        </div>
                                    </template>
                                </li>
                            </template>
                        </ul>

                        <p x-show="showsHint(current)" class="mt-2 text-gray-600" role="status">
                            {{ __('comment::annotations.replies.author_hint') }}
                        </p>

                        <div x-show="(canReply(current) && !replying) || canToggle(current) || canDelete(current) || current.can_report" class="mt-3 flex flex-wrap justify-end gap-2">
                            <template x-if="canEdit(current)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="edit()">
                                    {{ __('comment::annotations.server_modal.edit') }}
                                </x-shared::button>
                            </template>
                            <template x-if="canDelete(current)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="remove()">
                                    {{ __('comment::annotations.drafts_modal.delete') }}
                                </x-shared::button>
                            </template>
                            <template x-if="canToggle(current)">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="toggleProcessed()" x-bind:disabled="busy">
                                    <span x-show="!current.is_processed">{{ __('comment::annotations.server_modal.mark_processed') }}</span>
                                    <span x-show="current.is_processed">{{ __('comment::annotations.server_modal.mark_unprocessed') }}</span>
                                </x-shared::button>
                            </template>
                            <template x-if="canReply(current) && !replying">
                                <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="startReply()">
                                    {{ __('comment::annotations.replies.reply') }}
                                </x-shared::button>
                            </template>
                            {{-- x-for keyed on the id, not x-if: paging between members keeps `current`
                                 truthy, and reportButton() reads its id once, at init. --}}
                            <template x-for="reportId in (current.can_report ? [current.id] : [])" :key="reportId">
                                <div x-data="reportButton('annotation', reportId)">
                                    <x-shared::button type="button" size="sm" color="error" data-testid="annotation-popover-report" title="{{ __('moderation::report.button') }}" aria-label="{{ __('moderation::report.button') }}" x-on:click="loadForm()" x-bind:disabled="loading">
                                        <span class="material-symbols-outlined" aria-hidden="true">flag</span>
                                    </x-shared::button>
                                    <div x-ref="modalContainer"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <p x-show="error" x-text="error" class="mt-2 text-red-600" role="alert"></p>

                {{-- Kept out of the x-if above: Quill must survive paging between members. --}}
                <div x-show="replying" class="mt-3">
                    <span class="sr-only">{{ __('comment::annotations.replies.body_label') }}</span>
                    <x-editor::rich-text
                        id="annotation-overlay-reply-editor"
                        name="annotation_overlay_reply_body"
                        toolbar="inline"
                        :max="$replyMax"
                        :nbLines="3"
                        :resizable="false"
                        isMandatory="true"
                    />
                    <p x-show="replyError" x-text="replyError" class="mt-2 text-red-600" role="alert"></p>
                    <div class="mt-2 flex flex-wrap justify-end gap-2">
                        <x-shared::button type="button" size="sm" color="neutral" :outline="true" x-on:click="cancelReply()">
                            {{ __('comment::annotations.replies.cancel') }}
                        </x-shared::button>
                        <x-shared::button type="button" size="sm" color="accent" x-on:click="sendReply()" x-bind:disabled="!replyValid || sending">
                            {{ __('comment::annotations.replies.send') }}
                        </x-shared::button>
                    </div>
                </div>
            </div>
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
