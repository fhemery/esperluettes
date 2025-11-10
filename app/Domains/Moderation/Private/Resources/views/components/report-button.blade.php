@props([
    'topicKey',    // e.g., 'profile', 'story', 'chapter', 'comment'
    'entityId',    // ID of the entity being reported
    'compact' => false,
    'size' => 'md',
])

<div x-data="reportButton('{{ $topicKey }}', {{ $entityId }})" x-cloak>
    {{-- Lightweight Report Button --}}
    <x-shared::button
        type="button"
        x-on:click="loadForm()"
        color="error"
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

{{-- JS registration moved to app/Domains/Moderation/Private/Resources/js/report-button.js and imported from Shared app.js --}}
