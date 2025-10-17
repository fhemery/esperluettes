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

{{-- JS registration moved to app/Domains/Moderation/Private/Resources/js/report-button.js and imported from Shared app.js --}}
