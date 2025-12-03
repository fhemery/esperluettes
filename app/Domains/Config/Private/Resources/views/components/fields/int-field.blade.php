@props(['constraints' => []])

<input 
    type="number" 
    x-model.number="currentValue"
    @if(isset($constraints['min'])) min="{{ $constraints['min'] }}" @endif
    @if(isset($constraints['max'])) max="{{ $constraints['max'] }}" @endif
    :disabled="saving"
    class="w-full px-3 py-2 border border-border rounded-lg bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary disabled:opacity-50"
/>
