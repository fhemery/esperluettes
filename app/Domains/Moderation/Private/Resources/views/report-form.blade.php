{{-- Server-rendered report form modal (loaded via AJAX) --}}
<div x-data="reportForm('{{ $topicKey }}', {{ $entityId }})" x-init="$nextTick(() => openModal())">
    <x-modal name="report-modal-{{ $topicKey }}-{{ $entityId }}" :show="true" maxWidth="md">
        <div class="p-6">
            {{-- Modal Header --}}
            <div class="flex justify-between items-center mb-4">
                <x-shared::title tag="h2" icon="report">
                    {{ __('moderation::report.modal_title') }}
                </x-shared::title>
                <x-shared::button type="button" x-on:click="closeModal()" color="neutral">
                    <span class="material-symbols-outlined">close</span>
                </x-shared::button>
                
            </div>

            {{-- Success Message --}}
            <div x-show="submitted" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="mt-4 text-sm text-gray-900" x-text="successMessage"></p>
                <x-shared::button type="button" x-on:click="closeModal()" color="neutral" :outline="true">
                    {{ __('moderation::report.close') }}
                </x-shared::button>
            </div>

            {{-- Form --}}
            <form x-show="!submitted" @submit.prevent="submitReport()">
                {{-- Reason Selection --}}
                <div class="mb-4">
                    <label for="reason_id" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('moderation::report.reason_label') }}
                    </label>
                    <select
                        id="reason_id"
                        x-model="form.reason_id"
                        class="w-full border-accent focus:border-accent/80 focus:ring-accent/80"
                        required
                    >
                        <option value="">{{ __('moderation::report.reason_placeholder') }}</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason->id }}">{{ $reason->label }}</option>
                        @endforeach
                    </select>
                    <p x-show="errors.reason_id" class="mt-1 text-sm text-red-600" x-text="errors.reason_id"></p>
                </div>

                {{-- Description (Optional) --}}
                <div class="mb-6">
                    <x-shared::input-label for="description" :required="false">
                        {{ __('moderation::report.description_label') }}
                    </x-shared::input-label>
                    <textarea
                        id="description"
                        x-model="form.description"
                        rows="4"
                        maxlength="1000"
                        class="w-full border-dsaaccent focus:border-accent/80 focus:ring-accent/80"
                        placeholder="{{ __('moderation::report.description_placeholder') }}"
                    ></textarea>
                    <p class="text-right mt-1 text-xs text-fg/50" x-text="'(' + (form.description?.length || 0) + ' / 1000)'"></p>
                    <p x-show="errors.description" class="mt-1 text-sm text-red-600" x-text="errors.description"></p>
                </div>

                {{-- Error Message --}}
                <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600" x-text="errorMessage"></p>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3">
                    <x-shared::button type="button" x-on:click="closeModal()" color="neutral" :outline="true">
                        {{ __('moderation::report.cancel') }}
                    </x-shared::button>
                    <x-shared::button x-bind:disabled="submitting" color="accent">
                        <span x-show="!submitting">{{ __('moderation::report.submit') }}</span>
                        <span x-show="submitting">
                            <span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                        </span>
                    </x-shared::button>
                </div>
            </form>
        </div>
    </x-modal>
</div>

<script>
function reportForm(topicKey, entityId) {
    return {
        submitting: false,
        submitted: false,
        form: {
            topic_key: topicKey,
            entity_id: entityId,
            reason_id: '',
            description: '',
        },
        errors: {},
        errorMessage: '',
        successMessage: '',

        openModal() {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'report-modal-' + topicKey + '-' + entityId }));
        },

        closeModal() {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'report-modal-' + topicKey + '-' + entityId }));
        },

        async submitReport() {
            this.submitting = true;
            this.errors = {};
            this.errorMessage = '';

            try {
                const response = await fetch('/moderation/report', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (data.success) {
                    this.submitted = true;
                    this.successMessage = data.message || '{{ __("moderation::report.submitted") }}';
                } else {
                    this.errorMessage = data.message || '{{ __("moderation::report.error") }}';
                }
            } catch (error) {
                this.errorMessage = '{{ __("moderation::report.error") }}';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
