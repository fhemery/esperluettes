<!-- Navigation Links -->
<div class="flex justify-between grow-1" id="desktop-nav">

    <!-- Links -->
    <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex uppercase">
        <x-nav-link :href="route('stories.index')" :active="request()->routeIs('stories.index')">
            {{ __('shared::navigation.stories') }}
        </x-nav-link>
        <x-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')">
            {{ __('shared::navigation.news') }}
        </x-nav-link>

        @if (Auth::user() && Auth::user()->hasVerifiedEmail())
        @if (Auth::user()->isAdmin())
        <x-nav-link :href="route('filament.admin.pages.dashboard')"
            :active="request()->routeIs('filament.admin.*')">
            {{ __('shared::navigation.admin') }}
        </x-nav-link>
        @endif
        @endif
    </div>

    <!-- Profile with dropdown -->
    <div class="hidden sm:flex sm:items-center sm:ms-6">
        <x-dropdown width="w-64" contentClasses="bg-bg">
            <x-slot name="trigger">
                
                <button
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                    :class="open? 'bg-primary/20' : ''"
                    >
                    @if(isset($currentProfile) && $currentProfile)
                    <img src="{{ $currentProfile->avatar_url }}" alt="avatar"
                        class="h-6 w-6 rounded-full me-2" />
                    <div>{{ $currentProfile->display_name }}</div>
                    @else
                    <div>{{ Auth::user()->email }}</div>
                    @endif

                    <div class="ms-1">
                        <i x-show="!open" class="material-symbols-outlined text-accent">arrow_drop_down</i>
                        <i x-show="open" class="material-symbols-outlined text-accent">close</i>
                    </div>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="-mt-2 border-2 border-primary bg-primary/20 py-2 px-4">
                    @if (Auth::user()->hasVerifiedEmail())
                    <x-responsive-nav-link :href="route('profile.show.own')" :active="request()->routeIs('profile.show.own')">
                        {{ __('shared::navigation.profile') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('account.edit')" :active="request()->routeIs('account.edit')">
                        {{ __('shared::navigation.account') }}
                    </x-responsive-nav-link>
                    @else
                    <x-responsive-nav-link :href="route('verification.notice')" :active="request()->routeIs('verification.notice')">
                        {{ __('shared::navigation.verify-email') }}
                    </x-responsive-nav-link>
                    @endif

                    <!-- Authentication -->
                     <div class="flex justify-center pt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <a onclick="event.preventDefault();
                                        this.closest('form').submit();">
                            <x-shared::button color="accent">
                                {{ __('shared::navigation.logout') }}
                            </x-shared::button>
                        </a>
                    </form>
                     </div>
                </div>
            </x-slot>
        </x-dropdown>
    </div>
</div>