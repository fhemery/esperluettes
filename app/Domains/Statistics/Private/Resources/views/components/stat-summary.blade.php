@props([
    'statisticKey',
    'label',
    'format' => 'number',
    'scopeType' => 'global',
    'scopeId' => null,
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $statValue = $queryService->getValue($statisticKey, $scopeType, $scopeId);
@endphp

<div {{ $attributes->class(['stat-summary surface-bg text-on-surface rounded-lg p-4']) }}>
    <x-statistics::digit
        :value="$statValue?->value"
        :format="$format"
        :label="$label"
        size="lg"
        class="items-start"
    />
</div>
