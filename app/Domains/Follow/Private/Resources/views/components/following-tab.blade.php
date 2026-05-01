<div class="flex flex-col gap-4">
    @if($isOwn)
    <div class="flex justify-end" data-follow-visibility-indicator>
        <x-shared::popover placement="bottom">
            <x-slot name="trigger">
                <span class="material-symbols-outlined text-xl text-gray-400 leading-none">
                    {{ $isHidden ? 'visibility_off' : 'visibility' }}
                </span>
            </x-slot>
            <div class="text-sm">
                <p>{{ $isHidden ? __('follow::follow.visibility.hidden') : __('follow::follow.visibility.visible') }}</p>
                <a href="{{ route('settings.index', ['tab' => 'profile']) }}" class="underline text-primary">
                    {{ __('follow::follow.visibility.preferences_link') }}
                </a>
            </div>
        </x-shared::popover>
    </div>
    @endif

    @if(count($following) > 0)
        <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($following as $profile)
                <li>
                    <a href="{{ route('profile.show', ['profile' => $profile->slug]) }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-surface-alt transition">
                        <x-shared::avatar
                            :src="$profile->avatar_url"
                            class="w-24 h-24 lg:w-[200px] lg:h-[200px] rounded-full"
                            :alt="$profile->display_name" />
                        <span class="font-medium text-center text-sm lg:text-base">{{ $profile->display_name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-center text-gray-500 py-8">{{ __('follow::follow.following_tab.empty') }}</p>
    @endif
</div>
