@props(['text', 'label' => 'Copié !'])

<button
    type="button"
    x-data="{ copied: false }"
    @click="navigator.clipboard.writeText(@js($text)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
    {{ $attributes->merge(['class' => 'relative group inline-flex items-center gap-1.5 cursor-copy']) }}
    title="Cliquer pour copier"
>
    {{ $slot }}
    <span class="material-symbols-outlined text-base text-fg/30 opacity-0 group-hover:opacity-100 transition-opacity leading-none select-none">content_copy</span>
    <span
        x-show="copied"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute -top-7 left-0 surface-accent text-on-surface text-xs px-2 py-0.5 rounded pointer-events-none whitespace-nowrap"
    >{{ $label }}</span>
</button>
