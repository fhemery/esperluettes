<x-app-layout>
    <div data-testid="dashboard" class="-mt-4 grid grid-cols-[1fr] sm:grid-cols-[1fr_1fr] lg:grid-cols-[1fr_1fr_1fr_1fr] gap-2 md:gap-4">
        <!-- News -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
            <x-news::carousel size="compact" />
            <!-- Add the ribbon -->
            <div class="h-10 bg-theme-ribbon"></div>
        </div>

        <!-- Bienvenue panel -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
            <x-dashboard::welcome-component />
        </div>

        <!-- Keep writing / Promotion status -->
        <div class="col-span-1">
            @if($isConfirmed)
                <x-story::keep-writing-component />
            @else
                <x-dashboard::promotion-status-component />
            @endif
        </div>

        <!-- Keep reading -->
        <div class="col-span-1">
            <x-story::keep-reading-component />
        </div>

        <!-- Calendar widget -->
        <div class="col-span-1 sm:col-span-2 min-w-0">
            <x-calendar::activity-list-component />
        </div>

        <!-- Discover random stories -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4 min-w-0">
            <x-story::random-stories-component />
        </div>
    </div>
</x-app-layout>