<x-app-layout size="md">
    <div class="p-4 flex-1 flex flex-col">


        <div class="flex flex-col sm:flex-row sm:items-center justify-between">
            <x-shared::title icon="notifications">
                {{ __('notifications::pages.index.title') }}
            </x-shared::title>
            @if (!empty($page->notifications))
                <div class="mb-3 self-end" x-data="notifPage({ url: '{{ route('notifications.markAllRead') }}', csrf: '{{ csrf_token() }}' })">
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
            <div class="surface-read text-on-surface p-4 min-h-[10rem]" x-data="notificationList({ 
                loadMoreUrl: '{{ route('notifications.loadMore') }}',
                initialOffset: {{ count($page->notifications) }},
                initialHasMore: {{ $page->hasMore ? 'true' : 'false' }},
                csrf: '{{ csrf_token() }}'
            })">
                <ul class="divide-y divide-fg" data-test-id="notifications-list" x-ref="notificationsList">
                    @foreach ($page->notifications as $n)
                        <x-notification::notification-item :notification="$n" />
                    @endforeach
                </ul>

                {{-- Load More Button --}}
                <div class="mt-4 flex flex-col items-center gap-2" x-show="hasMore">
                    <x-shared::button 
                        color="accent" 
                        x-on:click="loadMore()"
                        x-bind:disabled="loading"
                        data-test-id="load-more-btn">
                        <span x-show="!loading">{{ __('notifications::pages.index.load_more') }}</span>
                        <span x-show="loading" x-cloak>{{ __('notifications::pages.index.loading') }}</span>
                    </x-shared::button>
                    <div x-show="error" x-cloak class="text-sm text-red-500" data-test-id="load-more-error">
                        {{ __('notifications::pages.index.load_more_error') }}
                    </div>
                </div>

                @once

                    <style>
                        .notif-item a {
                            color: rgb(var(--color-accent));
                            font-weight: bold;
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

                        function notificationList({
                            loadMoreUrl,
                            initialOffset,
                            initialHasMore,
                            csrf
                        }) {
                            return {
                                currentOffset: initialOffset,
                                hasMore: initialHasMore,
                                loading: false,
                                error: false,

                                async loadMore() {
                                    if (this.loading || !this.hasMore) return;

                                    this.loading = true;
                                    this.error = false;

                                    try {
                                        const response = await fetch(`${loadMoreUrl}?offset=${this.currentOffset}`, {
                                            method: 'GET',
                                            headers: {
                                                'X-CSRF-TOKEN': csrf,
                                                'Accept': 'text/html',
                                            },
                                            credentials: 'same-origin',
                                        });

                                        if (!response.ok) {
                                            throw new Error('Failed to load more notifications');
                                        }

                                        const html = await response.text();
                                        const hasMoreHeader = response.headers.get('X-Has-More');

                                        // Append new items to the list
                                        if (html.trim()) {
                                            this.$refs.notificationsList.insertAdjacentHTML('beforeend', html);
                                            // Count new items added (rough estimate)
                                            const parser = new DOMParser();
                                            const doc = parser.parseFromString(html, 'text/html');
                                            const newItemsCount = doc.querySelectorAll('li.notif-item').length;
                                            this.currentOffset += newItemsCount;
                                        }

                                        this.hasMore = hasMoreHeader === 'true';
                                    } catch (e) {
                                        console.error('Failed to load more notifications', e);
                                        this.error = true;
                                    } finally {
                                        this.loading = false;
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
