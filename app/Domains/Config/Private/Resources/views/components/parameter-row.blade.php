@props(['definition', 'value', 'isOverridden'])

@php
    $name = __($definition->nameTranslationKey());
    $description = __($definition->descriptionTranslationKey());
    $searchContent = $definition->domain . ' ' . $definition->key . ' ' . $name . ' ' . $description;
    $updateUrl = route('config.admin.parameters.update', ['domain' => $definition->domain, 'key' => $definition->key]);
    $resetUrl = route('config.admin.parameters.reset', ['domain' => $definition->domain, 'key' => $definition->key]);
@endphp

<div 
    x-data="parameterRow(@js($definition->type->value), @js($value), @js($isOverridden), @js($definition->default), '{{ $updateUrl }}', '{{ $resetUrl }}')"
    data-search-content="{{ $searchContent }}"
    class="flex flex-col md:flex-row md:items-center gap-4 p-4 hover:bg-surface-alt/30 transition-colors"
    :class="{ 'opacity-50': saving }"
>
    {{-- Name & Description --}}
    <div class="flex-1 min-w-0">
        <div class="font-medium text-fg flex items-center gap-2">
            {{ $name }}
            <template x-if="isOverridden">
                <span class="text-xs px-2 py-0.5 bg-primary/10 text-primary rounded-full">
                    {{ __('config::admin.parameters.overridden') }}
                </span>
            </template>
        </div>
        <div class="text-sm text-fg/60 mt-0.5">{{ $description }}</div>
        <div class="text-xs text-fg/40 font-mono mt-1">{{ $definition->domain }}.{{ $definition->key }}</div>
        
        @if(!empty($definition->constraints))
            <div class="text-xs text-fg/40 mt-1">
                @if(isset($definition->constraints['min']))
                    <span>Min: {{ $definition->constraints['min'] }}</span>
                @endif
                @if(isset($definition->constraints['max']))
                    <span class="ml-2">Max: {{ $definition->constraints['max'] }}</span>
                @endif
                @if(isset($definition->constraints['min_length']))
                    <span>Min length: {{ $definition->constraints['min_length'] }}</span>
                @endif
                @if(isset($definition->constraints['max_length']))
                    <span class="ml-2">Max length: {{ $definition->constraints['max_length'] }}</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Type-specific input --}}
    <div class="w-full md:w-64 shrink-0">
        @switch($definition->type->value)
            @case('bool')
                <x-shared::fields.bool-field />
                @break
            @case('int')
                <x-shared::fields.int-field :constraints="$definition->constraints" />
                @break
            @case('string')
                <x-shared::fields.string-field :constraints="$definition->constraints" />
                @break
            @case('time')
                <x-shared::fields.time-field />
                @break
        @endswitch
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-2 shrink-0">
        {{-- Save button --}}
        <button 
            @click="save()" 
            :disabled="!isDirty || saving"
            class="px-4 py-2 bg-primary text-on-primary rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors"
        >
            <span class="material-symbols-outlined text-sm" x-show="!saving">save</span>
            <span class="material-symbols-outlined text-sm animate-spin" x-show="saving" x-cloak>progress_activity</span>
            <span class="text-sm">{{ __('config::admin.parameters.save') }}</span>
        </button>

        {{-- Reset button --}}
        <button 
            x-show="isOverridden || isDirty"
            @click="reset()" 
            :disabled="saving"
            class="px-4 py-2 bg-surface-alt text-fg border border-border rounded-lg hover:bg-surface-alt/80 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors"
            title="{{ __('config::admin.parameters.reset_tooltip') }}"
        >
            <span class="material-symbols-outlined text-sm">restart_alt</span>
        </button>
    </div>

    {{-- Error message --}}
    <template x-if="error">
        <div class="w-full text-sm text-red-600 mt-2" x-text="error"></div>
    </template>
</div>

@once
@push('scripts')
<script>
    function parameterRow(type, initialValue, isOverridden, defaultValue, updateUrl, resetUrl) {
        // Get time mixin if this is a time field (function defined in time-field.blade.php)
        const timeMixin = type === 'time' && typeof timeFieldMixin === 'function' 
            ? timeFieldMixin(initialValue) 
            : {};

        return {
            type: type,
            originalValue: initialValue,
            currentValue: initialValue,
            isOverridden: isOverridden,
            defaultValue: defaultValue,
            updateUrl: updateUrl,
            resetUrl: resetUrl,
            saving: false,
            error: null,

            // Spread time-specific fields if applicable
            ...timeMixin,

            init() {
                // Initialize time field watchers if applicable
                if (this.type === 'time' && this.initTimeField) {
                    this.initTimeField();
                }
            },

            get isDirty() {
                if (this.type === 'bool') {
                    return Boolean(this.currentValue) !== Boolean(this.originalValue);
                }
                return this.currentValue !== this.originalValue;
            },

            async save() {
                this.saving = true;
                this.error = null;

                try {
                    const response = await fetch(this.updateUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ value: this.currentValue }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.error = data.message || 'An error occurred';
                        return;
                    }

                    this.originalValue = this.currentValue;
                    this.isOverridden = true;
                } catch (e) {
                    this.error = 'Network error';
                } finally {
                    this.saving = false;
                }
            },

            async reset() {
                this.saving = true;
                this.error = null;

                try {
                    const response = await fetch(this.resetUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.error = data.message || 'An error occurred';
                        return;
                    }

                    this.currentValue = this.defaultValue;
                    this.originalValue = this.defaultValue;
                    this.isOverridden = false;

                    // Reset time display if applicable
                    if (this.type === 'time' && this.resetTimeDisplay) {
                        this.resetTimeDisplay(this.defaultValue);
                    }
                } catch (e) {
                    this.error = 'Network error';
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endpush
@endonce
