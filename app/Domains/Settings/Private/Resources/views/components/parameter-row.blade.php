@props(['definition', 'value', 'isOverridden'])

@php
    $name = __($definition->nameKey);
    $description = $definition->descriptionKey ? __($definition->descriptionKey) : null;
    $updateUrl = route('settings.update', ['tab' => $definition->tabId, 'key' => $definition->key]);
    $resetUrl = route('settings.reset', ['tab' => $definition->tabId, 'key' => $definition->key]);
@endphp

<div
    x-data="settingsParameterRow(@js($definition->type->value), @js($value), @js($isOverridden), @js($definition->default), '{{ $updateUrl }}', '{{ $resetUrl }}', @js($definition->constraints))"
    class="flex flex-col md:flex-row md:items-center gap-4 p-4 hover:bg-surface-alt/30 transition-colors"
    :class="{ 'opacity-50': saving }"
>
    {{-- Name & Description --}}
    <div class="flex-1 min-w-0">
        <div class="font-medium text-fg flex items-center gap-2">
            {{ $name }}
            <template x-if="isOverridden">
                <span class="text-xs px-2 py-0.5 bg-primary/10 text-primary rounded-full">
                    {{ __('settings::settings.overridden') }}
                </span>
            </template>
        </div>
        @if($description)
            <div class="text-sm text-fg/60 mt-0.5">{{ $description }}</div>
        @endif

        @if(!empty($definition->constraints))
            <div class="text-xs text-fg/40 mt-1">
                @if(isset($definition->constraints['min']))
                    <span>Min: {{ $definition->constraints['min'] }}</span>
                @endif
                @if(isset($definition->constraints['max']))
                    <span class="ml-2">Max: {{ $definition->constraints['max'] }}</span>
                @endif
                @if(isset($definition->constraints['step']))
                    <span class="ml-2">{{ __('settings::settings.step') }}: {{ $definition->constraints['step'] }}</span>
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
            @case('enum')
                <x-shared::fields.enum-field :constraints="$definition->constraints" />
                @break
            @case('range')
                <x-shared::fields.range-field :constraints="$definition->constraints" />
                @break
            @case('multi')
                <x-shared::fields.multi-select-field :constraints="$definition->constraints" />
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
            <span class="text-sm">{{ __('settings::settings.save') }}</span>
        </button>

        {{-- Reset button --}}
        <button
            x-show="isOverridden || isDirty"
            @click="reset()"
            :disabled="saving"
            class="px-4 py-2 bg-surface-alt text-fg border border-border rounded-lg hover:bg-surface-alt/80 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors"
            title="{{ __('settings::settings.reset_tooltip') }}"
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
    function settingsParameterRow(type, initialValue, isOverridden, defaultValue, updateUrl, resetUrl, constraints) {
        // Get time mixin if this is a time field
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
            constraints: constraints || {},
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
                if (this.type === 'multi') {
                    return JSON.stringify(this.currentValue) !== JSON.stringify(this.originalValue);
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
                        this.error = data.message || '{{ __("settings::settings.error") }}';
                        return;
                    }

                    this.originalValue = this.currentValue;
                    this.isOverridden = this.currentValue !== this.defaultValue;
                } catch (e) {
                    this.error = '{{ __("settings::settings.network_error") }}';
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
                        this.error = data.message || '{{ __("settings::settings.error") }}';
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
                    this.error = '{{ __("settings::settings.network_error") }}';
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endpush
@endonce
