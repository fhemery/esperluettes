<div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
    <div class="pt-2 pb-3 space-y-1">
        <x-responsive-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')">
            {{ __('shared::navigation.news') }}
        </x-responsive-nav-link>
        <x-responsive-nav-link :href="route('stories.index')" :active="request()->routeIs('stories.index')">
            {{ __('shared::navigation.stories') }}
        </x-responsive-nav-link>
        @if (Auth::user())
            @if(Auth::user()->hasVerifiedEmail())
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('shared::navigation.dashboard') }}
                </x-responsive-nav-link>
            @endif
            @if(Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('filament.admin.pages.dashboard')"
                                       :active="request()->routeIs('filament.admin.*')">
                    {{ __('shared::navigation.admin') }}
                </x-responsive-nav-link>
            @endif
    </div>

    <!-- Responsive Settings Options -->
    <div class="pt-4 pb-1 border-t border-gray-200">
        <div class="px-4">
            @if(isset($currentProfile) && $currentProfile)
                <div class="flex items-center gap-2">
                    <img src="{{ $currentProfile->avatar_url }}" alt="avatar" class="h-8 w-8 rounded-full"/>
                    <div>
                        <div class="font-medium text-base text-gray-800">{{ $currentProfile->display_name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            @else
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            @endif
        </div>

        <div class="mt-3 space-y-1">
            @if (Auth::user()->hasVerifiedEmail())
                <x-responsive-nav-link :href="route('profile.show.own')">
                    {{ __('shared::navigation.profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('account.edit')">
                    {{ __('shared::navigation.account') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('verification.send')">
                    {{ __('shared::navigation.verify-email') }}
                </x-responsive-nav-link>
            @endif

            <!-- Authentication -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                                       onclick="event.preventDefault();
                                        this.closest('form').submit();">
                    {{ __('shared::navigation.logout') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</div>
@else
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                {{ __('shared::navigation.login') }}
            </x-responsive-nav-link>
            @if (Route::has('register'))
                <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                    {{ __('shared::navigation.register') }}
                </x-responsive-nav-link>
            @endif
        </div>
    </div>
@endif
