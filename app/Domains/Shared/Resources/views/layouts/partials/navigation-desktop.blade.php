<!-- Navigation Links -->
<div class="flex flex-1 justify-between items-center gap-4 grow-1" id="desktop-nav">

    <div class="ml-8">
        <!-- Global Search -->
        <x-search::components.header-search />
    </div>

    <div class="flex items-center gap-4">
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

        <!-- Profile with drawer -->
        <div class="hidden sm:flex sm:items-center sm:ms-6">
            <button
                @click="$dispatch('drawer-open-profile')"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                @if(isset($currentProfile) && $currentProfile)
                <x-shared::avatar :src="$currentProfile->avatar_url" alt="avatar" class="h-10 w-10 rounded-full me-2" />
                @endif
            </button>
        </div>
    

    <!-- Desktop-only Profile Drawer -->
    <x-shared::drawer name="profile" class="hidden sm:block">
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

        <div class="mt-1 space-y-1">
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
    </x-shared::drawer>
    </div>
</div>