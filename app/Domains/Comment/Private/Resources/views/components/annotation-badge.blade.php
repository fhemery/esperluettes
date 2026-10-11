{{-- Header badge: how many annotations the viewer sees on the chapter (all of
     them for the author, their own for a commenter), and, on click, the list.
     Choosing one shows the annotations in the text and opens it. Lives in the
     page's metrics row, beside <x-quote::author-badge>.

     Reads Alpine.store('annotationOverlay'), registered by the bundle that
     <x-comment::annotation-overlay> pushes for a viewer with an in-chapter role:
     for anyone else the store is undefined and nothing renders. --}}
<div x-data="{}">
    <template x-if="$store.annotationOverlay?.role">
        <div x-data
             x-show="$store.annotationOverlay.role === 'author' || $store.annotationOverlay.rows.length > 0"
             data-testid="annotation-badge">
            <x-shared::popover placement="top" maxWidth="22rem">
                <x-slot name="trigger">
                    <x-shared::badge color="neutral" size="xs" icon="edit_note">
                        <span x-text="$store.annotationOverlay.rows.length"></span>
                    </x-shared::badge>
                </x-slot>

                <div x-data="annotationList">
                    <div class="font-semibold text-gray-900 text-center"
                         x-text="count === 1 ? @js(__('comment::annotations.badge.one')) : @js(__('comment::annotations.badge.many')).replace(':count', count)"></div>
                    <p x-show="count === 0" class="mt-1 text-gray-700 text-center">{{ __('comment::annotations.server_modal.empty') }}</p>
                    <ul x-show="count > 0" class="mt-2 flex flex-col gap-1 max-h-72 overflow-y-auto" data-annotation-list>
                        <template x-for="item in items" :key="item.key">
                            <li>
                                <button type="button"
                                        class="w-full flex items-start gap-2 text-left rounded px-1 py-0.5 hover:bg-gray-100"
                                        :class="item.processed && 'opacity-60'"
                                        @click="select(item)">
                                    <img :src="item.avatar" alt="" class="h-5 w-5 mt-0.5 shrink-0 rounded-full object-cover">
                                    <span class="grow min-w-0">
                                        <span class="block text-xs text-gray-500 truncate" x-text="item.name"></span>
                                        <span class="block line-clamp-2 italic" x-text="item.text"></span>
                                    </span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </x-shared::popover>
        </div>
    </template>
</div>
