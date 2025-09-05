<!DOCTYPE html>
<!-- Season hardcoded by default, we'll improve after -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="autumn">
    <head>  
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @hasSection('title')
            <title>@yield('title')</title>
        @else
            <title>{{ config('app.name', 'Laravel') }}</title>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Google Material Symbols -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

        <!-- Quill, temporary solution -->
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

        @stack('meta')

        <!-- Scripts -->
        @stack('styles')
        @vite(['app/Domains/Shared/Resources/css/app.scss', 'app/Domains/Shared/Resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('shared::layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            @if (session('status') || session('success') || session('error') || $errors->any())
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    @if (session('status'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded bg-blue-100 border-l-4 border-blue-500 text-blue-700">
                            <p>{{ session('status') }}</p>
                            <button @click="show = false" class="text-blue-700 hover:text-blue-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('success'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded bg-green-100 border-l-4 border-green-500 text-green-700">
                            <p>{{ session('success') }}</p>
                            <button @click="show = false" class="text-green-700 hover:text-green-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded bg-red-100 border-l-4 border-red-500 text-red-700">
                            <p>{{ session('error') }}</p>
                            <button @click="show = false" class="text-red-700 hover:text-red-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if ($errors->any())
                        <div class="flex flex-col mb-4 p-4 rounded bg-red-100 border-l-4 border-red-500 text-red-700">
                            <div class="flex justify-between items-center mb-2">
                                <p class="font-medium">{{ __('Whoops! Something went wrong.') }}</p>
                                <button @click="show = false" class="text-red-700 hover:text-red-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
