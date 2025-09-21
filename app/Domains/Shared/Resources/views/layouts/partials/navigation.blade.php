<!-- The $currentProfile variable is injected by the SharedServiceProvider -->
<nav x-data>
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">
        <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-14" id="header-logo">
                    </a>
                </div>

                @include('shared::layouts.partials.navigation-desktop')

        </div>
    </div>
    <div class="min-h-6 bg-primary w-full"></div>
</nav>
