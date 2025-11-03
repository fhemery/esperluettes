@if($shouldDisplay)
<div class="flex items-center shrink-0">
    <a href="{{ route('notifications.index') }}" class="relative inline-flex items-center p-2 text-fg hover:text-fg/80 focus:outline-none transition ease-in-out duration-150">
        <span class="material-symbols-outlined">
            notifications
        </span>
        @if($unreadCount > 0)
        <span data-test-id="unread-badge" class="absolute top-1 right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-accent rounded-full">
            {{ $unreadCount }}
        </span>
        @endif
    </a>
</div>
@endif
