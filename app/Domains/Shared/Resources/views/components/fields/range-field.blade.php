{{--
    Range field component - renders as a slider with value display.
    
    Props:
    - constraints: array with 'min', 'max', and optional 'step' keys
    
    Expected Alpine.js context:
    - currentValue: number
    - saving: boolean
--}}
@props(['constraints' => []])

@php
    $min = $constraints['min'] ?? 0;
    $max = $constraints['max'] ?? 100;
    $step = $constraints['step'] ?? 1;
@endphp

<div class="flex items-center gap-3">
    <input 
        type="range" 
        x-model.number="currentValue"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        :disabled="saving"
        class="flex-1 h-2 bg-gray-200 appearance-none cursor-pointer accent-primary disabled:opacity-50 disabled:cursor-not-allowed"
    />
    <span 
        class="min-w-[3rem] text-center text-sm font-medium text-fg bg-surface-alt px-2 py-1 rounded"
        x-text="currentValue"
    ></span>
</div>
