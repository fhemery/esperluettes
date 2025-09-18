<!DOCTYPE html>
<!-- Season hardcoded by default, we'll improve after -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="autumn">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.png') }}">

        @include('shared::layouts.partials.head')
    </head>
    <body class="font-sans antialiased">
        @php
            $seasonalBackground = filter_var($attributes->get('seasonal-background', false), FILTER_VALIDATE_BOOLEAN);
            $class = $seasonalBackground ? 'bg-seasonal' : '';
        @endphp
        <div class="min-h-screen bg-bg text-fg h-full flex flex-col">
            @include('shared::layouts.partials.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            @if (session('status') || session('success') || session('error') || $errors->any())
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    @if (session('status'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded surface-info border-l-4 border-info-fg text-on-surface">
                            <p>{{ session('status') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('success'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded surface-success border-l-4 border-success-fg text-on-surface">
                            <p>{{ session('success') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="flex items-center justify-between mb-4 p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
                            <p>{{ session('error') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if ($errors->any())
                        <div class="flex flex-col mb-4 p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
                            <div class="flex justify-between items-center mb-2">
                                <p class="font-medium">{{ __('Whoops! Something went wrong.') }}</p>
                                <button @click="show = false" class="text-fg hover:text-fg/90">
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
            <main class="flex-1 flex flex-col w-full py-4 sm:py-8 {{ $class }}">
                <div class="max-w-7xl w-full mx-auto sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>

            @include('shared::components.footer')
        </div>
        @stack('scripts')
        
    </body>
</html>
