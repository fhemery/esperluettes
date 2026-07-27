{{-- The owner-facing visibility indicator is rendered by the profile page from
     the tab's ProfileTabPrivacy declaration, not here. --}}
<div class="flex flex-col gap-4">
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
