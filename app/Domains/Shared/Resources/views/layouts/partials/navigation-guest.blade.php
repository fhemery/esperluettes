<nav class="bg-bg text-fg">
    <div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">
        <div class="h-20 flex justify-between">
            <div class="flex items-center shrink-0">
                <a href="{{ route('home') }}" class="hidden md:block">
                    <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-16" id="header-logo">
                </a>
                <a href="{{ route('home') }}" class="block md:hidden">
                    <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-16" id="header-logo">
                </a>
            </div>

            <!-- Right actions -->
            <div class="flex items-center gap-2 sm:gap-4 md:gap-8">
                <x-nav-link :href="route('stories.index')" :active="request()->routeIs('stories.index')" class="uppercase h-full">
                    {{ __('shared::navigation.stories') }}
                </x-nav-link>
                <x-nav-link :href="route('news.index')" :active="request()->routeIs('news.index')" class="uppercase h-full">
                    {{ __('shared::navigation.news') }}
                </x-nav-link>
            </div>
            
            @unless (request()->routeIs('login'))
            <div class="h-full flex items-center justify-center">
                <a href="{{ route('login') }}">
                    <x-shared::button color="accent">{{ __('shared::navigation.login') }}</x-shared::button>
                </a>
            </div>
            @endunless
        </div>
    </div>
    <div class="min-h-8 bg-primary w-full"></div>
</nav>