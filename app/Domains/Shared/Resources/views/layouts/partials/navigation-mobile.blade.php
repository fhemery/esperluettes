<div class="sm:hidden" id="mobile-nav">
    <!-- Overlay -->
    <div class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[1px] motion-reduce:transition-none" @click="open = false"
        x-show="open" x-cloak
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"></div>
    <!-- Sliding Panel -->
    <div class="fixed inset-y-0 right-0 z-50 w-80 min-w-[300px] max-w-full motion-reduce:transition-none" id="mobile-drawer"
         x-show="open" x-cloak
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        <div class="h-full w-full bg-bg shadow-xl ring-1 ring-accent/10 overflow-y-auto flex flex-col">
            <!-- Header with Brand + Close -->
            <div class="sticky top-0 z-10 flex items-center justify-between px-4 py-3 border-b-2 border-primary bg-white/70 backdrop-blur supports-[backdrop-filter]:bg-white/60">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto"/>
                </div>
                <button @click="open = false" class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none" aria-label="Close menu">
                    <i class="material-symbols-outlined text-accent">close</i>
                </button>
            </div>
            <div class="px-4 py-4 space-y-6">
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
                            <img src="{{ $currentProfile->avatar_url }}" alt="avatar" class="h-8 w-8 rounded-full" />
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
        </div>
    </div>
</div>