<!-- Flash Messages -->
@php($errors = $errors ?? null)
@if (session('status') || session('success') || session('error') || $errors?->any())
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)"
        class="fixed top-24 right-4 z-50 w-full max-w-sm sm:max-w-md px-2 space-y-2">
        @if (session('status'))
            <div
                class="flex items-center justify-between p-4 rounded surface-info border-l-4 border-info-fg text-on-surface">
                <p>{{ session('status') }}</p>
                <button @click="show = false" class="text-fg hover:text-fg/90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('success'))
            <div
                class="flex items-center justify-between p-4 rounded surface-success border-l-4 border-success-fg text-on-surface">
                <p>{{ session('success') }}</p>
                <button @click="show = false" class="text-fg hover:text-fg/90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div
                class="flex items-center justify-between p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
                <p>{{ session('error') }}</p>
                <button @click="show = false" class="text-fg hover:text-fg/90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        @endif
        

        @if ($errors?->any())
            <div class="flex flex-col p-4 rounded surface-error border-l-4 border-error-fg text-on-surface">
                <div class="flex justify-between items-center mb-2">
                    <p class="font-medium">{{ __('Whoops! Something went wrong.') }}</p>
                    <button @click="show = false" class="text-fg hover:text-fg/90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
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
