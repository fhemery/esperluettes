@props([
    'type' => 'button',
    'color' => 'primary', // primary | accent | tertiary | success | danger | neutral
    'icon' => null,
    'size' => 'md', // xs | sm | md | lg,
    'disabled' => false,
    'outline' => false,
])

@php
    $base = 'inline-flex items-center justify-center rounded-md font-medium transition ease-in-out duration-150 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed';

    // Size map
    $sizes = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    // Color variants (explicit class strings so Tailwind JIT picks them up)
    $variants = [
        'primary' => 'surface-primary text-on-surface border-surface hover:border-surface/90 focus:ring-primary/40',
        'accent' => 'surface-accent text-on-surface border-surface hover:border-surface/90 focus:ring-accent/40',
        'tertiary' => 'surface-tertiary text-on-surface border-surface hover:border-surface/90 focus:ring-tertiary/40',
        'success' => 'surface-success text-on-surface border-surface hover:border-surface/90 focus:ring-green-500/40',
        'danger' => 'surface-danger text-on-surface border-surface hover:border-surface/90 focus:ring-red-500/40',
        'neutral' => 'surface-neutral text-on-surface border-surface hover:border-surface/90 focus:ring-gray-500/40',
    ];
    $colorClasses = $variants[$color] ?? $variants['primary'];
    if ($outline) {
        // Use a custom modifier class to avoid clashing with Tailwind's `outline` utility
        $colorClasses = $colorClasses. ' is-outline';
    }
@endphp

<button
    x-data="SubmitBtn()"
    {{ $attributes->merge(['type' => $type, 'class' => "$base $sizeClasses $colorClasses", 'disabled' => $disabled]) }}>
    @if ($icon)
        <span class="material-symbols-outlined">{{ $icon }}</span>
    @endif
    {{ $slot }}
    <span x-cloak x-show="loading" class="js-btn-spinner ml-2 inline-block h-4 w-4 border-2 border-current border-t-transparent rounded-full animate-spin" aria-hidden="true"></span>
</button>

@once
@push('scripts')
<script>
(function(){
  if (window.SubmitBtn) return;
  window.SubmitBtn = function(){
    return {
      loading: false,
      disableButton(){
        if (this.$el.disabled) return;
        this.loading = true;
        this.$el.disabled = true;
        this.$el.setAttribute('aria-busy', 'true');
      },
      init(){
        const type = (this.$el.getAttribute('type') || 'button').toLowerCase();
        if (type !== 'submit') return;
        const form = this.$el.form;
        if (!form) return;
        form.addEventListener('submit', (e) => {
          if (e.defaultPrevented) return;
          if (e.submitter && e.submitter !== this.$el) return;
          requestAnimationFrame(() => this.disableButton());
        });
      }
    }
  }
})();
</script>
@endpush
@endonce
