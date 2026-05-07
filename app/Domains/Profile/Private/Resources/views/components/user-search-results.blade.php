@foreach ($profiles as $profile)
    <button
        type="button"
        data-user-id="{{ $profile['id'] }}"
        data-name="{{ $profile['display_name'] }}"
        data-avatar-url="{{ $profile['avatar_url'] }}"
        class="flex items-center gap-3 w-full px-3 py-2 hover:bg-surface-read/70 text-left"
    >
        <x-shared::avatar
            :src="$profile['avatar_url']"
            :alt="$profile['display_name']"
            class="h-8 w-8 flex-shrink-0"
        />
        <span class="text-sm truncate">{{ $profile['display_name'] }}</span>
    </button>
@endforeach
