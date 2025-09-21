<x-app-layout seasonal-background="true">
    <div class="flex flex-col h-full items-center justify-center py-12 px-8 flex-1" data-test-id="home-page">
        <!-- Center translucent panel -->
        <div class="w-full max-w-[600px] bg-dark/90 text-white rounded-lg shadow-xl px-6 py-4">
            <!-- Top logo placeholder -->
            <div class="w-full rounded-sm flex items-center justify-center">
                <img src="{{ asset('images/themes/default/logo-white.png') }}" alt="{{config('app.name')}}" class="h-32">
            </div>

            <div class="mt-4 space-y-4 leading-relaxed text-center text-white"> {!! __('home::index.welcome-message') !!}</div>

            <div class="mt-4 flex items-center justify-center">
                <a href="{{ route('register') }}" class="btn-accent">{{ __('home::index.join-us') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
