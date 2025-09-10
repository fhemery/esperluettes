<div
    x-data="{ open: false }"
    x-on:drawer-open-{{ $name }}.window="open = true"
    x-on:drawer-close-{{ $name }}.window="open = false"
    x-on:keydown.window.escape="open = false"
    {{ $attributes->merge(['class' => '']) }}
>
    <!-- Overlay -->
    <div class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[1px] motion-reduce:transition-none"
         x-show="open" x-cloak
         @click="open = false"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <!-- Sliding Panel -->
    <div class="fixed inset-y-0 right-0 z-50 w-80 min-w-[300px] max-w-full motion-reduce:transition-none"
         :id="'drawer-'+{{ json_encode($name) }}"
         x-show="open" x-cloak
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
        <div class="h-full w-full bg-bg shadow-xl ring-1 ring-accent/10 overflow-y-auto flex flex-col">
            <!-- Header with Brand + Close -->
            <div class="sticky top-0 z-10 flex items-center justify-between px-4 py-3 border-b-2 border-primary bg-white/70 backdrop-blur supports-[backdrop-filter]:bg-white/60">
                <div class="flex items-center gap-3">
                    {{ $header ?? '' }}
                </div>
                <button @click="open = false" class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none" aria-label="Close menu">
                    <i class="material-symbols-outlined text-accent">close</i>
                </button>
            </div>

            <div class="px-4 py-4 space-y-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
