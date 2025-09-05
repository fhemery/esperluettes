<!DOCTYPE html>
<!-- Guest layout: minimal chrome, no navigation -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="autumn" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('shared::layouts.partials.head')
    </head>
    <body class="font-sans antialiased bg-bg text-fg flex flex-col h-full">
        @include('shared::layouts.partials.navigation-guest')

        <!-- Page Content (guest) -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        <!-- Shared footer -->
        <x-shared::footer />

        @stack('scripts')
    </body>
</html>
