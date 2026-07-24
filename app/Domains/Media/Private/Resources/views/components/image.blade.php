@props([
    'path' => null,
    'alt' => '',
    'widths' => [400, 800],
    'sizes' => '(max-width: 640px) calc(100vw - 2rem), 800px',
    'loading' => 'lazy',
    'caption' => null,
    'imgClass' => 'w-full h-auto',
])

@php
    $api = app(\App\Domains\Media\Public\Api\MediaPublicApi::class);
    $widths = is_array($widths) ? $widths : [400, 800];
    $maxWidth = $widths ? max($widths) : 800;
    $srcset = function (string $format) use ($api, $path, $widths) {
        return collect($widths)
            ->map(fn ($w) => $api->variantUrl($path, (int) $w, $format) . ' ' . (int) $w . 'w')
            ->implode(', ');
    };
@endphp

@if($path)
    <figure {{ $attributes->merge(['class' => 'media-image']) }}>
        <picture>
            <source type="image/webp" srcset="{{ $srcset('webp') }}">
            <img
                class="{{ $imgClass }}"
                src="{{ $api->variantUrl($path, (int) $maxWidth, 'jpg') }}"
                srcset="{{ $srcset('jpg') }}"
                sizes="{{ $sizes }}"
                alt="{{ $alt }}"
                loading="{{ $loading }}">
        </picture>
        @if($caption)
            <figcaption class="text-center text-sm text-gray-500 mt-1">{{ $caption }}</figcaption>
        @endif
    </figure>
@endif
