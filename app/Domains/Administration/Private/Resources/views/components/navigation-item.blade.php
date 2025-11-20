@props([
    'href' => '#',
    'label' => '',
    'icon' => null,
    'active' => false,
    'class' => 'text-sm',
])

<a href="{{ $href }}"
    class="group flex items-center px-3 text-fg hover:text-fg/80
      {{ $active ? 'text-tertiary' : '' }} {{ $class }}" data-test-id="admin-sidebar-link">

    <!-- Icon -->
    <span class="flex-shrink-0 w-5 mr-2">
        @if ($icon)
            <span class="material-symbols-outlined text-lg">
                {{ $icon }}
            </span>
        @endif
    </span>

    <!-- Label -->
    <span class="flex-1">{{ $label }}</span>
</a>
