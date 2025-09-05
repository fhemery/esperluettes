<nav class="bg-bg text-fg">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6">
        <div class="h-24 flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-20" id="header-logo">
                </a>
            </div>

            <!-- Right actions -->
            <div class="flex items-center gap-3 -mt-1">
                @unless (request()->routeIs('login'))
                    <a href="{{ route('login') }}" class="h-[40px]">
                        <x-shared::button color="accent">{{ __('shared::navigation.login') }}</x-shared::button>
                    </a>
                @endunless
            </div>
        </div>
    </div>
    <div class="min-h-8 bg-primary w-full"></div>
</nav>
