@props(['value', 
    'required' => false, 
    'size' => 'sm', // xs | sm | md | lg 
    'color' => 'fg',
])

<label {{ $attributes->merge(['class' => 'flex items-center gap-1 font-medium no-wrap text-'.$size.' text-'.$color]) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-error" aria-hidden="true">*</span>
    @endif
</label>
