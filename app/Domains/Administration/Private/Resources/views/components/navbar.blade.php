<!-- The $currentProfile variable is injected by the SharedServiceProvider -->
@php($additionalClass = !app()->environment('production') ? 'nonprod-nav' : '')
<nav x-data class="{{ $additionalClass }}">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">
        <div class="flex justify-between h-16 gap-4 md:gap-8">
            <!-- Logo + Admin mention -->
            <div class="shrink-0 flex items-center gap-4">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-14" id="header-logo">
                </a>
                <span class="text-2xl font-bold text-primary">{{__('administration::navigation.title')}}</span>
            </div>
            <!-- Back to site button -->
            <a href="{{ route('dashboard') }}" class="inline-flex items-center py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                <x-shared::button color="neutral" :outline="true">
                    {{ __('administration::navigation.back-to-site') }}
                </x-shared::button>
            </a>
        </div>
    </div>
    <div class="min-h-6 bg-primary w-full"></div>
</nav>