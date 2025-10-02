@props(['value', 
    'required' => false, 
    'size' => 'sm', // xs | sm | md | lg 
    'color' => 'fg',
])

<label {{ $attributes->merge(['class' => 'block font-medium text-'.$size.' text-'.$color]) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-error" aria-hidden="true">*</span>
    @endif
</label>
