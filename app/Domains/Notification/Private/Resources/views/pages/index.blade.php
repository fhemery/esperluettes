<x-app-layout size="md">
    <div class="p-4 flex-1 flex flex-col">


        <div class="flex items-center justify-between">
            <x-shared::title icon="notifications">
                {{ __('notifications::pages.index.title') }}
            </x-shared::title>
            @if (!empty($page->notifications))
                <div class="mb-3" x-data="notifPage({ url: '{{ route('notifications.markAllRead') }}', csrf: '{{ csrf_token() }}' })">
                    <x-shared::button color="accent" data-test-id="mark-all-read" x-on:click="markAll()">
                        {{ __('notifications::pages.index.mark_all_read') }}
                    </x-shared::button>
                </div>
            @endif
        </div>

        @if (empty($page->notifications))
            <div class="flex-1 surface-read text-on-surface p-4 min-h-[10rem] flex items-center justify-center">
                {{ __('notifications::pages.index.empty') }}
            </div>
        @else
            <div class="surface-read text-on-surface p-4 min-h-[10rem]">
                <ul class="divide-y divide-fg" data-test-id="notifications-list">
                    @foreach ($page->notifications as $n)
                        <li class="py-2 w-full grid grid-cols-[auto_1fr_auto] gap-4 notif-item" x-data="notifItem({
                            id: {{ $n->id }},
                            markUrl: '{{ route('notifications.markRead', $n->id) }}',
                            unmarkUrl: '{{ route('notifications.markUnread', $n->id) }}',
                            csrf: '{{ csrf_token() }}',
                            isRead: {{ $n->readAt ? 'true' : 'false' }},
                        })"
                            data-test-id="notif-item" data-content-key="{{ $n->contentKey }}">
                            {{-- Avatar --}}
                            <div class="col-span-1 row-span-2 self-center">
                                @if (!empty($n->avatarUrl))
                                    <x-shared::avatar class="h-10 w-10 sm:h-16 sm:w-16" :src="$n->avatarUrl" borderColor="accent" />
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="col-span-2"
                                :class="isRead ? '' : 'font-bold'">{!! __($n->contentKey, $n->contentData ?? []) !!}</div>

                            {{-- Date --}}
                            <div class="grid-col-1 flex items-center justify-between mt-1">
                                <div class="text-sm text-fg/70">
                                    {{ \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::parse($n->createdAt)->diffForHumans()) }}</div>
                            </div>

                            {{--  Read --}}
                            <div class="grid-rows-2 self-center" x-on:mark-read.stop="mark()" x-on:mark-unread.stop="unmark()">
                                <x-shared::read-toggle :read="(bool) $n->readAt" />
                            </div>
                        </li>
                    @endforeach
                </ul>

                @once

                    <style>
                        .notif-item a {
                            color: rgb(var(--color-accent));
                        }

                        .notif-item a:hover {
                            text-decoration: underline;
                        }
                    </style>

                    <script>
                        function notifPage({
                            url,
                            csrf
                        }) {
                            return {
                                async markAll() {
                                    try {
                                        await fetch(url, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrf,
                                                'Accept': 'application/json',
                                            },
                                            credentials: 'same-origin',
                                        });
                                        window.location.reload();
                                    } catch (e) {
                                        console.error('Failed to mark all as read', e);
                                    }
                                }
                            }
                        }

                        function notifItem({
                            id,
                            markUrl,
                            unmarkUrl,
                            csrf,
                            isRead
                        }) {
                            return {
                                isRead: !!isRead,
                                async mark() {
                                    try {
                                        await fetch(markUrl, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrf,
                                                'Accept': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                read: 1
                                            }),
                                            credentials: 'same-origin',
                                        });
                                        this.isRead = true;
                                    } catch (e) {
                                        console.error('Failed to mark as read', e);
                                    }
                                },
                                async unmark() {
                                    try {
                                        await fetch(unmarkUrl, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrf,
                                                'Accept': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                read: 0
                                            }),
                                            credentials: 'same-origin',
                                        });
                                        this.isRead = false;
                                    } catch (e) {
                                        console.error('Failed to mark as unread', e);
                                    }
                                }
                            }
                        }
                    </script>
                @endonce
            </div>
        @endif
    </div>
</x-app-layout>
