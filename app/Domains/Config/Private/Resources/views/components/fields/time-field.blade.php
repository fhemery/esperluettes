<div class="flex gap-2">
    <input 
        type="number" 
        x-model.number="timeDisplayValue"
        min="0"
        :disabled="saving"
        class="w-24 px-3 py-2 border border-border rounded-lg bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary disabled:opacity-50"
    />
    <select 
        x-model="timeUnit"
        :disabled="saving"
        class="flex-1 px-3 py-2 border border-border rounded-lg bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary disabled:opacity-50"
    >
        <option value="1">{{ __('config::admin.parameters.time_units.seconds') }}</option>
        <option value="60">{{ __('config::admin.parameters.time_units.minutes') }}</option>
        <option value="3600">{{ __('config::admin.parameters.time_units.hours') }}</option>
        <option value="86400">{{ __('config::admin.parameters.time_units.days') }}</option>
    </select>
</div>

@once
@push('scripts')
<script>
    /**
     * Find the best time unit to display a given number of seconds.
     * Returns { value, unit } where value * unit = seconds.
     */
    function findBestTimeUnit(seconds) {
        if (seconds === 0) return { value: 0, unit: 1 };
        if (seconds % 86400 === 0) return { value: seconds / 86400, unit: 86400 };
        if (seconds % 3600 === 0) return { value: seconds / 3600, unit: 3600 };
        if (seconds % 60 === 0) return { value: seconds / 60, unit: 60 };
        return { value: seconds, unit: 1 };
    }

    /**
     * Time field mixin for parameterRow Alpine component.
     * Call with initial seconds value to get time-specific properties and methods.
     */
    function timeFieldMixin(initialSeconds) {
        const initial = findBestTimeUnit(initialSeconds);
        
        return {
            timeDisplayValue: initial.value,
            timeUnit: initial.unit,

            initTimeField() {
                this.$watch('timeDisplayValue', () => this.updateCurrentValueFromTime());
                this.$watch('timeUnit', () => this.updateCurrentValueFromTime());
            },

            updateCurrentValueFromTime() {
                this.currentValue = Math.floor(this.timeDisplayValue * this.timeUnit);
            },

            resetTimeDisplay(seconds) {
                const display = findBestTimeUnit(seconds);
                this.timeDisplayValue = display.value;
                this.timeUnit = display.unit;
            }
        };
    }
</script>
@endpush
@endonce
