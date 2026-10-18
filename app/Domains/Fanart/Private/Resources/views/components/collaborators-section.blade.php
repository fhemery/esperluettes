@if ($visible)
    <div class="surface-read text-on-surface p-4">
        <x-shared::title tag="h2">{{ __('fanart::ui.fanartists.title') }}</x-shared::title>
        <p class="text-sm text-gray-500 mb-3">{{ __('fanart::ui.fanartists.description') }}</p>

        <div class="flex flex-col gap-2">
            @forelse ($fanartists as $fanartist)
                <div class="flex items-center justify-between p-3 bg-bg rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-shared::avatar :src="$fanartist['avatar_url']" :alt="$fanartist['display_name']" class="h-10 w-10" />
                        @if ($fanartist['slug'] !== '')
                            <a href="{{ route('profile.show', ['profile' => $fanartist['slug']]) }}" class="font-medium hover:text-accent">
                                {{ $fanartist['display_name'] }}
                            </a>
                        @else
                            <span class="font-medium">{{ $fanartist['display_name'] }}</span>
                        @endif
                    </div>
                    <form action="{{ route('fanart.fanartists.destroy', ['slug' => $slug, 'userId' => $fanartist['user_id']]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-error hover:text-error/80" title="{{ __('fanart::ui.fanartists.revoke') }}">
                            <span class="material-symbols-outlined">person_remove</span>
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('fanart::ui.fanartists.empty') }}</p>
            @endforelse
        </div>

        <form action="{{ route('fanart.fanartists.store', ['slug' => $slug]) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <x-shared::input-label for="fanartist-profile">{{ __('fanart::ui.fanartists.add_label') }}</x-shared::input-label>
                <x-profile::profile-and-role-picker :profiles-only="true" />
                @error('target_users')
                    <p class="text-sm text-error mt-1">{{ $message }}</p>
                @enderror
            </div>
            <x-shared::button color="accent" type="submit">{{ __('fanart::ui.fanartists.add_button') }}</x-shared::button>
        </form>

        {{ $slot }}
    </div>
@endif
