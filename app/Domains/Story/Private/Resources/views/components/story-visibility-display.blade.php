@props([
    'visibility' => 'public', // public | community | private
])

@php
  $options = [
    'public' => [
        'icon' => 'check_circle',
        'label' => __('story::shared.visibility.options.public'),
    ],
    'community' => [
        'icon' => 'group',
        'label' => __('story::shared.visibility.options.community'),
    ],
    'private' => [
        'icon' => 'visibility_off',
        'label' => __('story::shared.visibility.options.private'),
    ],
  ];
@endphp

<x-shared::popover placement="top">
<x-slot name="trigger">
<div
  class="inline-flex items-center gap-1 rounded-full px-1.5 py-1 shadow-inner select-none"
  role="img"
  aria-label="{{ __('story::shared.visibility.label') }}: {{ $options[$visibility]['label'] ?? $visibility }}"
>
  @foreach($options as $value => $meta)
    <div
      class="relative w-6 h-6 flex items-center justify-center rounded-full"
      title="{{ $meta['label'] }}"
    >
      @if($visibility === $value)
        <span
          class="absolute inset-0 rounded-full -m-1 bg-success/50"
          aria-hidden="true"
        ></span>
      @endif
      <span class="material-symbols-outlined relative text-black/80" aria-hidden="true">
        {{ $meta['icon'] }}
      </span>
    </div>
  @endforeach
</div>
</x-slot>   
<div>{{ $options[$visibility]['label'] }}</div>
</x-shared::popover>

