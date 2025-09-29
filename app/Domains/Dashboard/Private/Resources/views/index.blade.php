<x-app-layout>
    <div class="-mt-8 lg:-mt-12 grid grid-cols-[1fr] sm:grid-cols-[1fr_1fr] lg:grid-cols-[1fr_1fr_1fr_1fr] gap-2 md:gap-4">
        <!-- News -->
        <div class="col-span-1 sm:col-span-2 lg:col-span-4">
            <x-news::carousel size="compact" />
            <!-- Add the ribbon -->
            <div class="col-span-1 lg:col-span-3 h-10 bg-[url('/images/themes/autumn/top-ribbon.png')] bg-repeat-x">
                
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

        <!-- Placeholder side image -->
        <div class="col-span-1 sm:col-span-2 flex flex-col items-center justify-center">
            <img src="{{ asset('images/errors/not-ready.png') }}" alt="Débrouissage en cours" class="max-w-full h-auto">
            <p class="mt-2 text-sm text-muted">Débrouissage en cours</p>
        </div>

    </div>
</x-app-layout>
