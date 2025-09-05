<x-app-layout>
    <div class="flex flex-col h-full" data-test-id="home-page">
        <!-- Patterned background area -->
        <div class="bg-seasonal flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 flex-1">
            <!-- Center translucent panel -->
            <div class="w-full max-w-[600px] bg-tertiary/90 text-white rounded-lg shadow-xl px-6 py-8">
                <!-- Top logo placeholder -->
                <div class="w-full rounded-sm flex items-center justify-center">
                    <img src="{{ asset('images/themes/default/logo-white.png') }}" alt="{{config('app.name')}}" class="h-24">
                </div>

                <div class="mt-12 space-y-4 leading-relaxed">
                    <p>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent elementum ipsum vitae velit sagittis
                        sodales. Suspendisse eu felis scelerisque, rhoncus dolor ac, blandit velit. Nunc mollis non risus id
                        eleifend.
                    </p>
                    <p>
                        Proin non risus et libero consectetur congue. Phasellus fringilla interdum dui eu egestas. Nam sit amet
                        suscipit. Proin luctus, libero vel auctor maximus, ante arcu lacinia nulla, sit amet sodales risus nisi at
                        tortor.
                    </p>
                    <p>
                        Sed sagittis odio in elit tincidunt aliquet. Nullam maximus felis a nibh molestie euismod. Pellentesque non
                        mattis augue.
                    </p>
                </div>

                <div class="mt-12 flex items-center justify-center">
                    <a href="{{ route('register') }}" class="btn-accent">{{ __('home::index.join-us') }}</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>