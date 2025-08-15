<x-app-layout>
    <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
        @if($carouselItems->count() > 0)
        <section class="w-full lg:max-w-4xl max-w-[335px] mb-6">
            @include('news::components.carousel', ['items' => $carouselItems])
        </section>
        @endif

    </div>
</x-app-layout>
