@props([
    'totalKey' => 'global.total_comments',
    'rootKey' => 'global.total_root_comments',
    'label',
    'format' => 'number',
    'scopeType' => 'global',
    'scopeId' => null,
])

@php
    $queryService = app(\App\Domains\Statistics\Private\Services\StatisticQueryService::class);
    $values = $queryService->getValues([$totalKey, $rootKey], $scopeType, $scopeId);
    $total = $values[$totalKey]?->value;
    $root = $values[$rootKey]?->value;
    $reply = ($total !== null && $root !== null) ? max(0, $total - $root) : null;
@endphp

<div {{ $attributes->class(['comment-summary surface-bg text-on-surface rounded-lg p-4']) }}>
    <x-statistics::digit
        :value="$total"
        :format="$format"
        :label="$label"
        size="lg"
        class="items-start"
    />

    @if($root !== null || $reply !== null)
        <p class="text-sm text-fg/60 mt-2">
            {{ __('statistics::admin.comments_breakdown', [
                'root' => $root !== null ? number_format($root, 0, ',', ' ') : '—',
                'reply' => $reply !== null ? number_format($reply, 0, ',', ' ') : '—',
            ]) }}
        </p>
    @endif
</div>
