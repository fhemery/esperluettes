{{--
    Multi-select field component - renders as a checkbox group.
    
    Props:
    - options: array of [value => translationKey] pairs
    
    Expected Alpine.js context:
    - currentValue: array of selected values
    - saving: boolean
--}}
@props(['options' => []])

<div class="space-y-2">
    @foreach($options as $value => $labelKey)
        <label class="flex items-center gap-2 cursor-pointer select-none">
            <input 
                type="checkbox" 
                value="{{ $value }}"
                x-model="currentValue"
                :disabled="saving"
                class="w-4 h-4 border-border text-primary focus:ring-primary focus:ring-2 disabled:opacity-50"
            />
            <span class="text-sm text-fg">{{ __($labelKey) }}</span>
        </label>
    @endforeach
</div>
