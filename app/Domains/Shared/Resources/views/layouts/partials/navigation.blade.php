<!-- The $currentProfile variable is injected by the SharedServiceProvider -->
<nav x-data>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">
        <div class="flex justify-between h-16 gap-4 sm:gap-8">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-14" id="header-logo">
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-1 justify-between items-center gap-8 grow-1">
                <div class="flex-1">
                    <!-- Global Search -->
                    <x-search::components.header-search />
                </div>

                <div class="flex items-center gap-8" id="desktop-nav-links">
                    <!-- Links -->
                    <div class="hidden sm:flex sm:gap-8 uppercase">
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

                </div>
            </div>
            <!-- Profile with drawer -->
            <div class="flex items-center shrink-0">
                <button
                    @click="$dispatch('drawer-open-profile')"
                    class="inline-flex items-center py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                    @if(isset($currentProfile) && $currentProfile)
                    <x-shared::avatar :src="$currentProfile->avatar_url" alt="avatar" class="h-10 w-10 rounded-full me-2" />
                    @endif
                </button>
            </div>
        </div>
    </div>
    <div class="min-h-6 bg-primary w-full"></div>
    <!-- Desktop-only Profile Drawer -->
    <x-shared::drawer name="profile">
        <x-slot:header>
            <div class="flex items-center gap-2">
                @if(isset($currentProfile) && $currentProfile)
                <x-shared::avatar :src="$currentProfile->avatar_url" alt="avatar" class="h-8 w-8 rounded-full" />
                <div>
                    <div class="font-medium text-base text-gray-800">{{ $currentProfile->display_name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
                @else
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                @endif
            </div>
        </x-slot:header>
        <div id="desktop-nav-drawer">

            <div class="block sm:hidden pt-2 space-y-1 pb-4 border-b-2 border-accent">
                <x-responsive-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')">
                    {{ __('shared::navigation.news') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('stories.index')" :active="request()->routeIs('stories.index')">
                    {{ __('shared::navigation.stories') }}
                </x-responsive-nav-link>
                @if(Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('filament.admin.pages.dashboard')"
                    :active="request()->routeIs('filament.admin.*')">
                    {{ __('shared::navigation.admin') }}
                </x-responsive-nav-link>
                @endif
            </div>

            <div class="space-y-1 mt-2">
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
                <form method="POST" action="{{ route('logout') }}" class="pt-2">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('shared::navigation.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </x-shared::drawer>

</nav>