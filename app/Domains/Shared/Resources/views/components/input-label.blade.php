@props(['value', 
    'required' => false, 
    'size' => 'sm', // xs | sm | md | lg 
    'color' => 'fg',
])

{{-- A label bound with `for="x"` gets the id `x_label`, so composite fields (date + time) can point aria-labelledby at it. --}}
@php($labelFor = $attributes->get('for'))
<label @if ($labelFor && ! $attributes->has('id')) id="{{ $labelFor }}_label" @endif {{ $attributes->merge(['class' => 'flex items-center gap-1 font-medium no-wrap text-'.$size.' text-'.$color]) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-error" aria-hidden="true">*</span>
    @endif
</label>
