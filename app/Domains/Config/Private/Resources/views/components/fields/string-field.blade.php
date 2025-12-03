@props(['constraints' => []])

<input 
    type="text" 
    x-model="currentValue"
    @if(isset($constraints['max_length'])) maxlength="{{ $constraints['max_length'] }}" @endif
    :disabled="saving"
    class="w-full px-3 py-2 border border-border rounded-lg bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary disabled:opacity-50"
/>
