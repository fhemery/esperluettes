{{--
    Boolean field component - renders as a toggle switch.
    
    Expected Alpine.js context:
    - currentValue: boolean
    - saving: boolean
--}}
<label class="inline-flex items-center cursor-pointer select-none">
    <input
        type="checkbox"
        x-model="currentValue"
        class="sr-only peer"
        :disabled="saving"
    >
    <span class="relative inline-block w-11 h-6 rounded-full bg-gray-300 transition-colors align-middle
        peer-focus:outline-none peer-focus:ring-2
        after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full
        after:bg-white after:shadow after:transition-transform
        peer-checked:after:translate-x-5
        peer-checked:bg-accent peer-focus:ring-accent/80"></span>
    <span class="ml-3 text-sm" x-text="currentValue ? '{{ __('shared::fields.enabled') }}' : '{{ __('shared::fields.disabled') }}'"></span>
</label>
