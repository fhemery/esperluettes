@props(['disabled' => false, 'color' => 'accent'])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-accent focus:border-accent/80 focus:ring-accent rounded-md']) }}>
