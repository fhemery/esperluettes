@props(['notification'])

<li class="py-2 w-full grid grid-cols-[auto_1fr_auto] gap-4 notif-item" 
    x-data="notifItem({
        id: {{ $notification->id }},
        markUrl: '{{ route('notifications.markRead', $notification->id) }}',
        unmarkUrl: '{{ route('notifications.markUnread', $notification->id) }}',
        csrf: '{{ csrf_token() }}',
        isRead: {{ $notification->readAt ? 'true' : 'false' }},
    })"
    data-test-id="notif-item"
    data-notification-id="{{ $notification->id }}">
    {{-- Avatar --}}
    <div class="col-span-1 row-span-2 self-center">
        @if (!empty($notification->avatarUrl))
            <x-shared::avatar class="h-10 w-10 sm:h-16 sm:w-16" :src="$notification->avatarUrl" borderColor="accent" />
        @elseif ($notification->isSystem)
            <div class="logo h-10 w-10 sm:h-16 sm:w-16 flex items-center justify-center rounded-full bg-primary/10 border-2 border-primary overflow-hidden">
                <img src="{{ $theme->logo() }}" alt="Logo" class="h-full w-full">
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="col-span-2"
        :class="isRead ? '' : 'font-bold'">{!! $notification->renderedContent !!}</div>

    {{-- Date --}}
    <div class="grid-col-1 flex items-center justify-between mt-1">
        <div class="text-sm text-fg/70">
            {{ \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::parse($notification->createdAt)->diffForHumans()) }}</div>
    </div>

    {{--  Read --}}
    <div class="grid-rows-2 self-center" x-on:mark-read.stop="mark()" x-on:mark-unread.stop="unmark()">
        <x-shared::read-toggle :read="(bool) $notification->readAt" />
    </div>
</li>

@once
@push('scripts')
<script>
    (function(){
        if (window.notifItem) return;
        
        window.notifItem = function({ id, markUrl, unmarkUrl, csrf, isRead }) {
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
                    // Dispatch custom event for notification icon to update
                    window.dispatchEvent(new CustomEvent('notification-read', { 
                        detail: { notificationId: id },
                        bubbles: true
                    }));
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
                    // Dispatch custom event for notification icon to update
                    window.dispatchEvent(new CustomEvent('notification-unread', { 
                        detail: { notificationId: id },
                        bubbles: true
                    }));
                } catch (e) {
                    console.error('Failed to mark as unread', e);
                }
            }
        };
    };
    })();
</script>
@endpush
@endonce
