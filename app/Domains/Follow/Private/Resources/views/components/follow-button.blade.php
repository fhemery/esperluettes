@if($canFollow)
    @if($isFollowing)
        <form method="POST" action="{{ route('follow.unfollow', $userId) }}">
            @csrf
            @method('DELETE')
            <x-shared::button type="submit" color="tertiary" outline="true">
                <span class="material-symbols-outlined text-[18px] leading-none">check</span>
                {{ __('follow::follow.button.following') }}
            </x-shared::button>
        </form>
    @else
        <form method="POST" action="{{ route('follow.follow', $userId) }}">
            @csrf
            <x-shared::button type="submit" color="tertiary">
            <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                {{ __('follow::follow.button.follow') }}
            </x-shared::button>
        </form>
    @endif
@endif
