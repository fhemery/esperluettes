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
            $displayRibbon = filter_var($attributes->get('display-ribbon', false), FILTER_VALIDATE_BOOLEAN);
        @endphp
        <div class="min-h-screen bg-bg text-fg h-full flex flex-col">
            @include('shared::layouts.partials.navigation')
            @if ($displayRibbon)
                <div class="w-full h-10 bg-[url('/images/themes/autumn/top-ribbon.png')] bg-repeat-x"></div>
            @endif

            {{-- Breadcrumbs --}}
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 pt-4">
                @if(!empty($breadcrumbs))
                    <x-shared::breadcrumbs :items="$breadcrumbs" />
                @endif
            </div>

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
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)" class="max-w-7xl mx-auto px-2 sm:px-6 sm:py-6 py-2">
                    @if (session('status'))
                        <div class="flex items-center justify-between p-4 rounded surface-info border-l-4 border-info-fg text-on-surface">
                            <p>{{ session('status') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('success'))
                        <div class="flex items-center justify-between p-4 rounded surface-success border-l-4 border-success-fg text-on-surface">
                            <p>{{ session('success') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="flex items-center justify-between p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
                            <p>{{ session('error') }}</p>
                            <button @click="show = false" class="text-fg hover:text-fg/90">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    @if ($errors->any())
                        <div class="flex flex-col p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
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
            <main class="flex-1 flex flex-col w-full {{ $class }}">
                <div class="max-w-7xl w-full mx-auto px-2 sm:px-6 lg:px-8 py-8 lg:py-12 flex-1 flex flex-col">
                    {{ $slot }}
                </div>
            </main>

            @include('shared::components.footer')
        </div>
        @stack('scripts')
        <script>
            (function () {
                const heartbeatIntervalMs = 5 * 60 * 1000; // 5 minutes

                function heartbeat() {
                    fetch("{{ route('session.heartbeat') }}", {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).catch(() => {});
                }

                function updateClientCsrf(token) {
                    // Update meta tag
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', token);

                    // Update hidden inputs named _token
                    document.querySelectorAll('input[name="_token"]').forEach(el => {
                        el.value = token;
                    });

                    // Update axios default header if axios is present
                    if (window.axios && window.axios.defaults && window.axios.defaults.headers) {
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                    }
                }

                // Prevent duplicate refreshes when multiple events fire together
                let csrfRefreshing = false;
                let lastCsrfRefreshAt = 0;
                const csrfCooldownMs = 1500; // 1.5s coalescing window

                async function refreshCsrf() {
                    const now = Date.now();
                    if (csrfRefreshing || (now - lastCsrfRefreshAt) < csrfCooldownMs) {
                        return;
                    }
                    csrfRefreshing = true;
                    try {
                        const res = await fetch("{{ route('session.csrf') }}", {
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data && data.token) updateClientCsrf(data.token);
                    } catch (_) { /* ignore */ }
                    finally {
                        csrfRefreshing = false;
                        lastCsrfRefreshAt = Date.now();
                    }
                }

                // Start heartbeat after load and at interval
                window.addEventListener('load', () => {
                    heartbeat();
                    setInterval(heartbeat, heartbeatIntervalMs);
                });

                // Refresh CSRF whenever the tab becomes active or page is shown from bfcache
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) refreshCsrf();
                }, { passive: true });

                window.addEventListener('focus', () => {
                    refreshCsrf();
                }, { passive: true });

                // Also refresh when network returns
                window.addEventListener('online', () => {
                    refreshCsrf();
                }, { passive: true });
            })();
        </script>
        
    </body>
</html>
