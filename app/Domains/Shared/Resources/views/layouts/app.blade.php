<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-season="{{ $theme->value }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
        <div class="w-full h-10 bg-theme-ribbon"></div>
        @endif

        <x-shared::flash-block />

        <!-- Page Content -->
        @php($cssSize = ($size==='sm') ? 'max-w-2xl' : (($size==='md') ? 'max-w-4xl' : 'max-w-7xl'))
        <main class="flex-1 flex flex-col w-full {{ $class }}">
            <div class="flex-1 w-full max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 pt-4 pb-6 lg:pb-8 flex flex-col gap-4">
                @if (isset($page) && $page->breadcrumbs)
                <x-shared::breadcrumbs-component :breadcrumbs="$page->breadcrumbs" />
                @else
                <x-shared::breadcrumbs-component />
                @endif
                <div class="w-full flex-1 flex flex-col {{ $cssSize }} mx-auto">
                    {{ $slot }}
                </div>
            </div>
        </main>

        @include('shared::components.footer')
    </div>
    @stack('scripts')
    <script>
        (function() {
            const heartbeatIntervalMs = 5 * 60 * 1000; // 5 minutes

            function heartbeat() {
                fetch("{{ route('session.heartbeat') }}", {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
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
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data && data.token) updateClientCsrf(data.token);
                } catch (_) {
                    /* ignore */ } finally {
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
            }, {
                passive: true
            });

            window.addEventListener('focus', () => {
                refreshCsrf();
            }, {
                passive: true
            });

            // Also refresh when network returns
            window.addEventListener('online', () => {
                refreshCsrf();
            }, {
                passive: true
            });
        })();
    </script>

</body>

</html>