<x-app-layout>
    <div class="-mt-4 grid grid-cols-[1fr] sm:grid-cols-[1fr_1fr] lg:grid-cols-[1fr_1fr_1fr_1fr] gap-2 md:gap-4">
        <!-- News -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
            <x-news::carousel size="compact" />
            <!-- Add the ribbon -->
            <div class="h-10 bg-[url('/images/themes/autumn/top-ribbon.png')] bg-repeat-x">

            </div>
        </div>

        <!-- Bienvenue panel -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
            <x-dashboard::welcome-component />
        </div>

        <!-- Keep writing -->
        <div class="col-span-1">
            <x-story::keep-writing-component />
        </div>

        <!-- Keep reading -->
        <div class="col-span-1">
            <x-story::keep-reading-component />
        </div>

        <!-- Calendar widget or placeholder -->
        @if($calendarEnabled)
            <div class="col-span-1 sm:col-span-2 min-w-0">
                <x-calendar::activity-list-component />
            </div>
        @else
            <div class="col-span-1 sm:col-span-2 flex flex-col gap-2 items-center justify-center p-4 surface-read text-on-surface">
                <x-shared::title tag="h3" icon="group" class="text-tertiary">
                    {{ __('dashboard::index.placeholder_title') }}
                </x-shared::title>
                <div class="flex-1">
                    <img src="{{ asset('images/errors/not-ready.png') }}" alt="{{ __('dashboard::index.placeholder_text') }}" class="max-w-full h-auto">
                </div>
                <p class="text-sm text-muted">{{ __('dashboard::index.placeholder_text') }}</p>
            </div>
        @endif

        <!-- Discover random stories -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4 min-w-0">
            <x-story::random-stories-component />
        </div>
    </div>
</x-app-layout>