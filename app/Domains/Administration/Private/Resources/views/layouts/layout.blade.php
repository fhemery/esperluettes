<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="{{ $theme->value }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('shared::layouts.partials.head')
</head>
<body class="min-h-screen" x-data="{ adminSidebarOpen: false }">
    <x-shared::flash-block />
    <div class="min-h-screen w-full bg-bg text-fg">
        <div class="md:grid md:grid-cols-[300px_1fr] md:grid-rows-[auto_1fr] md:max-h-screen">
            <div class="md:col-span-2">
                <x-administration::navbar />
            </div>
            <!-- Desktop sidebar -->
            <div class="hidden md:block md:col-span-1 overflow-y-auto">
                <x-administration::sidebar />
            </div>
            <main class="p-4 max-w-7xl w-full mx-auto overflow-y-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Mobile slide-in sidebar -->
    <div
        class="fixed inset-0 z-40 flex md:hidden"
        x-show="adminSidebarOpen"
        x-transition.opacity
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/40" x-on:click="adminSidebarOpen = false"></div>

        <!-- Panel -->
        <div
            class="relative bg-bg w-72 max-w-full h-full z-50 flex flex-col"
            x-transition:enter="transition transform ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition transform ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            <div class="p-2 border-b border-border flex justify-end flex-shrink-0">
                <button
                    type="button"
                    class="p-1 text-fg"
                    x-on:click="adminSidebarOpen = false"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <x-administration::sidebar />
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
