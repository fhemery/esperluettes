<nav class="bg-bg text-fg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-32 flex items-center justify-between">
            <!-- Logo placeholder -->
            <div class="flex items-center">
                <!-- TODO: Make seasonal logo -->
                <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-24">
            </div>

            <!-- Right actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="btn-accent">{{ __('shared::navigation.login') }}</a>
            </div>
        </div>
    </div>
    <div class="min-h-16 bg-primary w-full"></div>
</nav>
