@php(/** @var \App\Domains\Auth\Private\Models\User|null $user */ $user = auth()->user())

<x-filament-panels::page>
    <div class="prose dark:prose-invert max-w-none">
        <x-shared::title>{{ __('admin::pages.view_logs.title') }}</x-shared::title>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('admin::pages.view_logs.description') }}</p>
        @if(!$user || !$user->hasRole('tech-admin'))
            <div class="mt-4 text-red-600 dark:text-red-400">
                {{ __('admin::pages.system_maintenance.no_permission') }}
            </div>
        @else
            <div class="mt-4 flex flex-col gap-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin::pages.view_logs.select_file') }}</label>
                        <select wire:model="file" class="mt-1 block rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach($availableFiles as $f)
                                <option value="{{ $f['file'] }}">{{ $f['file'] }} ({{ number_format($f['size']/1024, 1) }} KB)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::button icon="heroicon-o-arrow-path" wire:click="refresh">
                            {{ __('admin::pages.view_logs.refresh') }}
                        </x-filament::button>
                        <a
                            href="{{ route('admin.logs.download', ['file' => $file]) }}"
                            class="fi-btn fi-color-gray inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50"
                        >
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                            {{ __('admin::pages.view_logs.download') }}
                        </a>
                    </div>
                </div>

                <div id="logs-container" class="border rounded-md border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 overflow-auto max-h-screen" style="height:65vh;">
                    <pre class="p-4 text-xs leading-5 whitespace-pre-wrap font-mono">@foreach($lines as $line){{ $line }}
@endforeach</pre>
                </div>
                <script>
                    (function () {
                        function scrollBottom() {
                            var el = document.getElementById('logs-container');
                            if (el) {
                                el.scrollTop = el.scrollHeight;
                            }
                        }
                        function setupObserver() {
                            var el = document.getElementById('logs-container');
                            if (!el) return;
                            var pre = el.querySelector('pre');
                            if (!pre) return;
                            var observer = new MutationObserver(function () { scrollBottom(); });
                            observer.observe(pre, { childList: true, subtree: true, characterData: true });
                        }
                        // Initial
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', function(){ scrollBottom(); setupObserver(); });
                        } else {
                            scrollBottom();
                            setupObserver();
                        }
                        // Livewire v3 navigation hook
                        window.addEventListener('livewire:navigated', function(){ scrollBottom(); });
                    })();
                </script>
            </div>
        @endif
    </div>
</x-filament-panels::page>
