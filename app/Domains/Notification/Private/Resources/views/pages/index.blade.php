<x-app-layout size="lg">
    <div class="p-4 flex-1 flex flex-col">
        <x-shared::title icon="notifications">
            {{ __('notifications::pages.index.title') }}
        </x-shared::title>

        <div class="mb-3" x-data="notifPage({ url: '{{ route('notifications.markAllRead') }}', csrf: '{{ csrf_token() }}' })">
            <x-shared::button color="accent" data-test-id="mark-all-read" x-on:click="markAll()">
                {{ __('notifications::pages.index.mark_all_read') }}
            </x-shared::button>
        </div>

        @if (empty($page->notifications))
            <div class="flex-1 surface-read text-on-surface p-4 min-h-[10rem] flex items-center justify-center">
                {{ __('notifications::pages.index.empty') }}
            </div>
        @else
            <div class="flex-1 surface-read text-on-surface p-4 min-h-[10rem]">
                <ul class="space-y-2" data-test-id="notifications-list">
                    @foreach ($page->notifications as $n)
                        <li class="p-3 rounded border border-border" x-data="notifItem({
                            id: {{ $n->id }},
                            markUrl: '{{ route('notifications.markRead', $n->id) }}',
                            unmarkUrl: '{{ route('notifications.markUnread', $n->id) }}',
                            csrf: '{{ csrf_token() }}',
                            isRead: {{ $n->readAt ? 'true' : 'false' }},
                        })" data-test-id="notif-item">
                            <div class="text-sm text-fg/70">{{ $n->createdAt }}</div>
                            <div class="font-medium {{ $n->readAt ? '' : 'font-bold' }}"
                                :class="isRead ? '' : 'font-bold'">{{ $n->contentKey }}</div>
                            <div class="mt-2" @markRead.stop="mark()" @markUnread.stop="unmark()">
                                <x-shared::read-toggle :read="(bool) $n->readAt" />
                            </div>
                        </li>
                    @endforeach
                </ul>

                @once

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
