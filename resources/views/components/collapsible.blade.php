@props([
    'title' => '',
    'open' => false,
])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="w-full mb-4 bg-white overflow-visible shadow-sm sm:rounded-lg">
    <button type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-left"
            @click="open = !open">
        <span class="text-gray-900 font-medium">{{ $title }}</span>
        <span class="material-symbols-outlined text-gray-600" x-text="open ? 'expand_less' : 'expand_more'"></span>
    </button>
    <div class="border-t border-gray-100" x-show="open" x-transition>
        <div class="p-6 text-gray-900">
            {{ $slot }}
        </div>
    </div>
</div>
