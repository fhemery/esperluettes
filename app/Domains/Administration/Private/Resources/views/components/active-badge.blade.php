@props([
    'active' => false,
    'activeLabel' => null,
    'inactiveLabel' => null,
])

@if($active)
    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success">
        {{ $activeLabel ?? __('administration::shared.active') }}
    </span>
@else
    <span class="inline-flex items-center px-2 py-1 text-xs bg-fg/10 text-fg/50">
        {{ $inactiveLabel ?? __('administration::shared.inactive') }}
    </span>
@endif
