@props([
    'value',
    'format' => 'number',
    'label' => null,
    'size' => 'md',
])

@php
    $formattedValue = match(true) {
        $value === null => '—',
        $format === 'compact' && $value >= 1000000 => number_format($value / 1000000, 1) . 'M',
        $format === 'compact' && $value >= 1000 => number_format($value / 1000, 1) . 'K',
        default => number_format($value, 0, ',', ' '),
    };
    
    $sizeClasses = match($size) {
        'sm' => 'text-lg',
        'lg' => 'text-4xl',
        'xl' => 'text-5xl',
        default => 'text-2xl',
    };
@endphp

<div {{ $attributes->class(['stat-digit flex flex-col items-center']) }}>
    <span class="stat-value font-bold {{ $sizeClasses }}" data-raw-value="{{ $value }}">
        {{ $formattedValue }}
    </span>
    @if($label)
        <span class="stat-label text-sm text-gray-500 mt-1">{{ $label }}</span>
    @endif
</div>
