<!DOCTYPE html>
<!-- Guest layout: minimal chrome, no navigation -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="autumn" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.png') }}">

        @include('shared::layouts.partials.head')
    </head>
    <body class="font-sans antialiased bg-bg text-fg flex flex-col h-full">
        @include('shared::layouts.partials.navigation-guest')

        @php
            // Attributes from the class-based component (<x-app-layout ...>)
            $seasonalBackground = filter_var($attributes->get('seasonal-background', false), FILTER_VALIDATE_BOOLEAN);
            $mainClass = $seasonalBackground ? 'bg-seasonal' : '';
        @endphp

        <!-- Page Content (guest) -->
        <main class="flex-1 flex flex-col w-full {{ $mainClass }}">
            <div class="w-full max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-8 lg:py-12">
                {{ $slot }}
            </div>
        </main>

        <!-- Shared footer -->
        <x-shared::footer />

        @stack('scripts')
    </body>
</html>
