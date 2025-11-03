@props([
    'name' => null,
    'options' => [], // array items: {id|value, name|label}
    'selected' => '',
    'placeholder' => '',
    'valueField' => 'id',
    'labelField' => 'name',
    'required' => false,
    'chevron' => false,
    'selectClass' => 'w-full appearance-none bg-transparent text-transparent caret-transparent pr-8 py-2',
])

@php
    $opts = collect($options)
        ->map(function ($o) use ($valueField, $labelField) {
            if (is_array($o)) {
                return [
                    'value' => (string) ($o[$valueField] ?? ($o['id'] ?? '')),
                    'label' => (string) ($o[$labelField] ?? ($o['name'] ?? '')),
                ];
            }
            return ['value' => (string) $o, 'label' => (string) $o];
        })
        ->values();
    $selectedValue = (string) $selected;
@endphp

<div x-data="selectTruncating({
            options: @js($opts),
            selected: @js($selectedValue),
            placeholder: @js($placeholder),
        })"
     {{ $attributes->merge(['class' => 'relative w-full']) }}>

    <div class="pointer-events-none absolute inset-y-0 left-0 right-8 flex items-center px-3">
        <span class="block w-full truncate text-sm"
              x-text="selectedLabel || placeholder"></span>
    </div>

    <select
        @change="onChange($event)"
        name="{{ $name }}"
        class="{{ $selectClass }}"
        {{ $required ? 'required' : '' }}
    >
        @foreach($opts as $opt)
            <option value="{{ $opt['value'] }}" {{ $opt['value'] === $selectedValue ? 'selected' : '' }} title="{{ $opt['label'] }}">{{ $opt['label'] }}</option>
        @endforeach
    </select>

    @if($chevron)
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            if (!window.selectTruncating) {
                window.selectTruncating = function ({ options, selected, placeholder }) {
                    return {
                        options,
                        placeholder,
                        state: { selected: selected ?? '' },
                        get selectedLabel() {
                            const o = this.options.find(x => x.value === this.state.selected);
                            return o ? o.label : '';
                        },
                        onChange(e) {
                            const v = e.target.value;
                            this.state.selected = v;
                            // Dispatch for external listeners
                            this.$el.dispatchEvent(new CustomEvent('select-change', { detail: { value: v }, bubbles: true }));
                        }
                    }
                }
            }
        </script>
    @endpush
@endonce
