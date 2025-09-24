@props([
    'overflowColor' => 'accent',
])

@php
    $overflowId = 'badge-overflow-' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
@endphp

<div class="flex items-start gap-2" x-data="badgeOverflow('{{ $overflowId }}')" x-ref="container">
    <x-shared::popover placement="top">
        <x-slot:trigger>
            <x-shared::badge color="{{ $overflowColor }}" size="xs" x-ref="countBadge">+<span x-text="count"></span></x-shared::badge>
        </x-slot:trigger>
        <div class="p-1" x-ref="overflow" id="{{ $overflowId }}"></div>
    </x-shared::popover>

    <div class="flex items-start gap-2 overflow-hidden w-full" x-ref="visible"></div>
    
    <div class="hidden" x-ref="hidden">
             {{ $slot }}
    </div>
</div>

@once
@push('scripts')
<script>
if (!window.badgeOverflow) {
    window.badgeOverflow = function(overflowId) {
        return {
            count: 0,
            init() {
                this.$nextTick(() => {
                try {
                    const hidden = this.$refs.hidden;
                    const visible = this.$refs.visible;
                    const container = this.$refs.container;
                    const overflow = document.getElementById(overflowId);
                    this.count = (hidden && hidden.children) ? hidden.children.length : 0;

                    // Move items one by one; if visible overflows after appending, move item to overflow and do NOT decrease count
                    while (hidden && hidden.firstElementChild) {
                        const el = hidden.firstElementChild;
                        visible.appendChild(el);
                        // Check overflow
                        const fits = visible.scrollWidth <= visible.clientWidth + 0.5;
                        if (fits) {
                            this.count--
                        } else {
                            overflow.appendChild(el);
                        }
                    }

                    // If count === 0, we have successfully moved all items to visible, so remove the badge responsible for overflow
                    // Else, add it back, but to the end instead                    
                    if (this.count === 0) {
                        container.removeChild(container.firstElementChild);
                    } else {
                        visible.appendChild(container.firstElementChild);
                    }
                } catch (_) {
                    this.count = 0;
                }
                });
            }
        }
    }
}
</script>
@endpush
@endonce
