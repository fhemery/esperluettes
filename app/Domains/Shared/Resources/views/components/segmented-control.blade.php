@props([
    // Identifies the control in the `segmented-control-change` event detail
    'name',
    // Options as value => label
    'options',
    // aria-label of the radio group
    'label',
    // Initial value (defaults to the first option)
    'selected' => null,
    // Optional localStorage key to remember the choice per browser
    'storageKey' => null,
])

@php
    $keys = array_map('strval', array_keys($options));
    $selectedKey = in_array((string) $selected, $keys, true) ? (string) $selected : ($keys[0] ?? '');
@endphp

<div
    role="radiogroup"
    aria-label="{{ $label }}"
    data-segmented-control
    data-name="{{ $name }}"
    data-value="{{ $selectedKey }}"
    x-data="segmentedControl({ name: @js($name), options: @js($keys), selected: @js($selectedKey), storageKey: @js($storageKey) })"
    {{ $attributes->merge(['class' => 'inline-flex rounded-full border border-primary overflow-hidden']) }}
>
    @foreach($options as $key => $optionLabel)
        @php($key = (string) $key)
        <button
            type="button"
            role="radio"
            aria-checked="{{ $key === $selectedKey ? 'true' : 'false' }}"
            tabindex="{{ $key === $selectedKey ? '0' : '-1' }}"
            data-value="{{ $key }}"
            :aria-checked="value === @js($key) ? 'true' : 'false'"
            :tabindex="value === @js($key) ? '0' : '-1'"
            :class="value === @js($key) ? 'surface-primary text-on-surface font-semibold' : 'text-fg hover:bg-primary/10'"
            @click="select(@js($key))"
            @keydown="onKeydown($event)"
            class="px-4 py-1.5 text-sm whitespace-nowrap focus:outline-hidden focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-accent"
        >
            {{ $optionLabel }}
        </button>
    @endforeach
</div>
