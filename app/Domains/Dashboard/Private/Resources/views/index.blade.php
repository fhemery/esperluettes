<x-app-layout>
    <div class="-mt-8 lg:-mt-12 grid grid-cols-[1fr] md:grid-cols-[auto_auto_1fr] gap-2 md:gap-4">
        <!-- News -->
        <div class="col-span-1 md:col-span-3">
            <x-news::carousel size="compact" />
            <!-- Add the ribbon -->
            <div class="col-span-1 md:col-span-3 h-10 bg-[url('/images/themes/autumn/top-ribbon.png')] bg-repeat-x">
                
            </div>
        </div>


    </div>
</x-app-layout>
