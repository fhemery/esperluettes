<x-shared::drawer name="mobile" class="sm:hidden">
    <x-slot:header>
        <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto" />
    </x-slot:header>
    <div id="mobile-nav">

        <div class="pt-2 space-y-1">
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

        <!-- Account Block -->
        <div class="pb-1 border-t-2 border-primary pt-4">
            <div class="">
                @if(isset($currentProfile) && $currentProfile)
                <div class="flex items-center gap-2">
                    <x-shared::avatar :src="$currentProfile->avatar_url" alt="avatar" class="h-8 w-8 rounded-full" />
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
</x-shared::drawer>