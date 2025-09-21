<nav class="bg-bg text-fg">
    <div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">
        <div class="h-16 flex gap-1 justify-between items-center">
            <div class="flex items-center shrink-0">
                <a href="{{ route('home') }}" class="hidden md:block">
                    <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-14" id="header-logo">
                </a>
                <a href="{{ route('home') }}" class="block md:hidden">
                    <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-14" id="header-logo">
                </a>
            </div>

            <div class="md:pl-8 flex-1">
                <!-- Global Search -->
                <x-search::components.header-search />
            </div>

            <div class="flex items-center justify-center sm:justify-end gap-1 sm:gap-2 md:gap-4 sm:px-4 md:px-8">
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
                    <x-shared::button size="sm" color="accent">{{ __('shared::navigation.login') }}</x-shared::button>
                </a>
            </div>
            @endunless
        </div>
    </div>
    <div class="h-6 bg-primary w-full"></div>
</nav>