@props([
    'topicKey',    // e.g., 'profile', 'story', 'chapter', 'comment'
    'entityId',    // ID of the entity being reported
    'buttonClass' => 'text-sm text-gray-600 hover:text-gray-900',
    'compact' => false,
    'size' => 'md',
])
@php
    $configApi = app(App\Domains\Config\Public\Contracts\ConfigPublicApi::class);
    if (!$configApi->isToggleEnabled('reporting', 'moderation')) {
        return;
    }
@endphp

<div x-data="reportButton('{{ $topicKey }}', {{ $entityId }})" x-cloak>
    {{-- Lightweight Report Button --}}
    <x-shared::button
        type="button"
        x-on:click="loadForm()"
        color="tertiary"
        x-bind:disabled="loading"
        :title="$compact ? __('moderation::report.button'): ''"
        :size="$size"
    >
        <span class="material-symbols-outlined">flag</span>
        @if(!$compact)
            {{ __('moderation::report.button') }}
        @endif
    </x-shared::button>

    {{-- Modal Container (populated via AJAX) --}}
    <div x-ref="modalContainer"></div>
</div>

@once
@push('scripts')
<script>
if (!window.__moderationReportRegistered) {
window.__moderationReportRegistered = true;

window.reportButton = function(topicKey, entityId) {
    return {
        loading: false,
        // container is populated via innerHTML so we can re-init Alpine

        async loadForm() {
            if (this.loading) return;
            
            this.loading = true;

            try {
                const response = await fetch(`/moderation/report-form/${topicKey}/${entityId}`);

                // If backend redirects (e.g., to login), follow it at window level
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                if (response.ok) {
                    const html = await response.text();
                    this.$refs.modalContainer.innerHTML = html;
                    // Ensure Alpine initializes the newly injected DOM
                    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(this.$refs.modalContainer);
                    }
                } else {
                    if (response.status === 401 || response.status === 403) {
                        // Likely unauthenticated or forbidden
                        window.location.href = '/login';
                        return;
                    }
                    alert('{{ __("moderation::report.error") }}');
                }
            } catch (error) {
                alert('{{ __("moderation::report.error") }}');
            } finally {
                this.loading = false;
            }
        },
    };
}

// Expose reportForm globally so injected HTML (loaded via innerHTML) can use it
window.reportForm = function(topicKey, entityId) {
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

                // Handle redirects or unauthorized at window level
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                if (response.status === 401 || response.status === 403) {
                    window.location.href = '/login';
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.submitted = true;
                    this.successMessage = data.message || '{{ __("moderation::report.submitted") }}';
                } else {
                    // Laravel may return validation errors as { errors: { field: [messages] } }
                    if (data.errors) {
                        // Map first error for each field to display
                        Object.keys(data.errors).forEach(k => this.errors[k] = data.errors[k][0] ?? '');
                    }
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
}
</script>
@endpush
@endonce
