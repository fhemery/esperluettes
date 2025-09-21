@props([
'visibility' => 'public',
])

@php
$label = match($visibility) {
'public' => __('story::shared.visibility.options.public'),
'community' => __('story::shared.visibility.options.community'),
'private' => __('story::shared.visibility.options.private'),
default => $visibility,
};
$color = match($visibility) {
'public' => 'success',
'community' => 'info',
'private' => 'warning',
default => 'neutral',
};
@endphp

<x-shared::popover placement="bottom" maxWidth="16rem">
    <x-slot name="trigger">
        <x-shared::badge :color="$color" size="xs">{{ $label }}</x-shared::badge>
    </x-slot>
    <div class="text-fg">{{ __('story::shared.visibility.help.'.$visibility) }}</div>
</x-shared::popover>