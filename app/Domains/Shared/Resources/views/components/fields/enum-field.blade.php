{{--
    Enum field component - renders as a dropdown select.
    
    Props:
    - options: array of [value => translationKey] pairs
    
    Expected Alpine.js context:
    - currentValue: string
    - saving: boolean
--}}
@props(['options' => []])

<select 
    x-model="currentValue"
    :disabled="saving"
    class="w-full px-3 py-2 border border-border bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary disabled:opacity-50"
>
    @foreach($options as $value => $labelKey)
        <option value="{{ $value }}">{{ __($labelKey) }}</option>
    @endforeach
</select>
