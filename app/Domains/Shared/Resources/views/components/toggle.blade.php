@props([
    'id' => null,
    'name',
    'checked' => false,
    'value' => '1',
    'label' => null,
    'disabled' => false,
    'btnColor' => 'accent', // accent | primary | secondary | tertiary
    'textColor' => 'fg', // fg | primary | secondary | tertiarty accent
])

<?php
    /** 
     * We are currently only handling a subset of colors, but feel free to extend is with any color you want
     * This is to help tailwind find and compile the correct classes, instead of safelisting them all 
     */
?>

<label @class([
    'inline-flex items-center select-none pl-1',
    'cursor-pointer'              => !$disabled,
    'cursor-not-allowed opacity-50' => $disabled,
])>
    {{-- Relative track box: checkbox overlays the visible switch so focus does not scroll a clipped sr-only node. --}}
    <span class="relative inline-block h-6 w-11 shrink-0 align-middle">
        <input
            @if($id) id="{{ $id }}" @endif
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            @class([
                'peer absolute inset-0 z-10 h-full w-full opacity-0',
                'cursor-pointer' => !$disabled,
                'cursor-not-allowed' => $disabled,
            ])
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
        >
        <span @class([
            'pointer-events-none relative block h-full w-full rounded-full bg-gray-300 transition-colors',
            'peer-focus:outline-hidden peer-focus:ring-2',
            'after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full',
            'after:bg-white after:shadow after:transition-transform',
            'peer-checked:after:translate-x-5',
            'peer-checked:bg-accent peer-focus:ring-accent/80' => $btnColor === 'accent',
            'peer-checked:bg-primary peer-focus:ring-primary/80' => $btnColor === 'primary',
            'peer-checked:bg-secondary peer-focus:ring-secondary/80' => $btnColor === 'secondary',
            'peer-checked:bg-tertiary peer-focus:ring-tertiary/80' => $btnColor === 'tertiary',
        ])></span>
    </span>
    @if($label)
        <span @class([
            'ml-3 text-sm',
            'text-fg' => $textColor === 'fg',
            'text-primary' => $textColor === 'primary',
            'text-secondary' => $textColor === 'secondary',
            'text-accent' => $textColor === 'accent',
        ])>{{ $label }}</span>
    @endif
</label>
