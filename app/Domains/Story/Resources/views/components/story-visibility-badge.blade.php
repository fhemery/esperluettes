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
$badgeClasses = match($visibility) {
'public' => 'bg-green-100 text-green-800 ring-green-600/20',
'community' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
'private' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
default => 'bg-gray-100 text-gray-800 ring-gray-600/20',
};
@endphp

<x-shared::popover placement="bottom" maxWidth="16rem">
    <x-slot name="trigger">
        <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">{{ $label }}</span>
    </x-slot>
    <div class="text-gray-700">{{ __('story::shared.visibility.help.'.$visibility) }}</div>
</x-shared::popover>