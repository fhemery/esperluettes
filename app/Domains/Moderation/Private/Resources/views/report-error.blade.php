{{-- Error view when report form cannot be loaded --}}
<div x-data="{ show: true }" x-init="$nextTick(() => show = true)">
    <x-modal name="report-error-modal" :show="true" maxWidth="md">
        <div class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-4 text-sm text-gray-900">{{ $message ?? __('moderation::report.error') }}</p>
            <button
                type="button"
                @click="$dispatch('close-modal', 'report-error-modal')"
                class="mt-4 px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800"
            >
                {{ __('moderation::report.cancel') }}
            </button>
        </div>
    </x-modal>
</div>
