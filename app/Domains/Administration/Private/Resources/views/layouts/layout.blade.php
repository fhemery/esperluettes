<!DOCTYPE html>
<!-- Season hardcoded by default, we'll improve after -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="autumn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('shared::layouts.partials.head')
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <x-shared::flash-block />
    <div class="min-h-screen w-full bg-bg text-fg h-full grid md:grid-cols-[250px_1fr] md:grid-rows-[auto_1fr]">
        <div class="col-span-2">
            <x-administration::navbar />
        </div>
        <div class="col-span-1">
            <x-administration::sidebar />
        </div>
        <main class="col-span-1 p-4">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
