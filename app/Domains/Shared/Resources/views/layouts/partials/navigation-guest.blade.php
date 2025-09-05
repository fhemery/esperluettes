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
                <a href="{{ route('login') }}" class="btn-accent h-[40px]">{{ __('shared::navigation.login') }}</a>
            </div>
        </div>
    </div>
    <div class="min-h-8 bg-primary w-full"></div>
</nav>
