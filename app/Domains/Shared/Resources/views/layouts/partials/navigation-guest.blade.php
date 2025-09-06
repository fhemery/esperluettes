<nav class="bg-bg text-fg">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6">
        <div class="h-24 flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="hidden md:block">
                    <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-20" id="header-logo">
                </a>
                <a href="{{ route('home') }}" class="block md:hidden">
                    <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-20" id="header-logo">
                </a>
            </div>

            <!-- Right actions -->
            <div class="flex items-center gap-4 md:gap-8 -mt-1">
                @unless (request()->routeIs('login'))
                    <a href="{{ route('stories.index') }}" class="uppercase">
                        {{ __('shared::navigation.stories') }}
                    </a>
                    <a href="{{ route('news.index') }}" class="uppercase">
                        {{ __('shared::navigation.news') }}
                    </a>
                    <a href="{{ route('login') }}" class="h-[40px]">
                        <x-shared::button color="accent">{{ __('shared::navigation.login') }}</x-shared::button>
                    </a>
                @endunless
            </div>
        </div>
    </div>
    <div class="min-h-8 bg-primary w-full"></div>
</nav>
