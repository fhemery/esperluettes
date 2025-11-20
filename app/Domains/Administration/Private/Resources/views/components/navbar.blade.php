<!-- The $currentProfile variable is injected by the SharedServiceProvider -->
@php($additionalClass = !app()->environment('production') ? 'nonprod-nav' : '')
<nav class="{{ $additionalClass }}">
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
            <div class="flex items-center gap-3">
                <!-- Back to site button (desktop) -->
                <a href="{{ route('dashboard') }}" class="hidden md:inline-flex items-center py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                    <x-shared::button color="neutral" :outline="true">
                        {{ __('administration::navigation.back-to-site') }}
                    </x-shared::button>
                </a>

                <!-- Mobile menu toggle -->
                <button
                    type="button"
                    class="inline-flex md:hidden items-center justify-center p-2 rounded-md text-fg hover:text-fg/80"
                    @click="adminSidebarOpen = true"
                >
                    <span class="material-symbols-outlined text-2xl">menu</span>
                </button>
            </div>
        </div>
    </div>
    <div class="min-h-6 bg-primary w-full"></div>
</nav>