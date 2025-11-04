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
