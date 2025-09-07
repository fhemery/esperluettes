@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-primary text-start text-base font-medium text-accent bg-primary/20 focus:outline-none focus:text-accent focus:bg-tertiary/20 focus:border-tertiary transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-primary/20 hover:border-primary focus:outline-none focus:text-accent focus:bg-tertiary/20 focus:border-tertiary transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
