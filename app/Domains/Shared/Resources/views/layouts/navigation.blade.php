<nav x-data="{ open: false }" @keydown.window.escape="open = false" class="border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('images/themes/autumn/logo.png') }}" alt="{{config('app.name')}}" class="h-16" id="header-logo">
                    </a>
                </div>

                @include('shared::layouts.partials.navigation-desktop')

                <!-- Hamburger -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="open = ! open"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            :aria-expanded="open.toString()" aria-controls="mobile-drawer">
                        <i class="material-symbols-outlined text-accent text-4xl">menu</i>
                    </button>
                </div>

        </div>
    </div>

    <!-- Mobile Drawer -->
    @include('shared::layouts.partials.navigation-mobile')
    <div class="min-h-8 bg-primary w-full"></div>
</nav>
