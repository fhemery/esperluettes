@props([
    'coverType' => 'default',
    'coverUrl' => null,
    'coverHdUrl' => null,
    'hd' => false,
    'width' => 150,
    'class' => '',
])

@php
    $widthClass = match ((int) $width) {
        300 => 'w-[300px]',
        230 => 'w-[230px]',
        default => 'w-[150px]',
    };
    $canLightbox = $hd && $coverHdUrl && $coverType !== 'default';
@endphp

<div {{ $attributes->merge(['class' => "{$widthClass} {$class}"]) }} aria-hidden="true"
    @if($canLightbox) x-data="{ lightboxOpen: false }" @endif
>
    @if ($coverType === 'default')
        <x-shared::default-cover class="{{ $widthClass }} object-contain" />
    @elseif($canLightbox)
        <img src="{{ $coverUrl }}" alt="" class="{{ $widthClass }} object-contain cursor-pointer"
            loading="lazy" @click="lightboxOpen = true" />

        {{-- Lightbox overlay --}}
        <template x-teleport="body">
            <div x-show="lightboxOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-black/70"
                @click.self="lightboxOpen = false"
                @keydown.escape.window="lightboxOpen = false">
                <img src="{{ $coverHdUrl }}" alt=""
                    class="max-w-[min(800px,95vw)] max-h-[95vh] object-contain"
                    loading="lazy" />
            </div>
        </template>
    @else
        <img src="{{ $coverUrl }}" alt="" class="{{ $widthClass }} object-contain" loading="lazy" />
    @endif
</div>
