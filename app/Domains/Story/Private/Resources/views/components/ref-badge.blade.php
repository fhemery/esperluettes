@props([
    'name' => '',
    'description' => null,
    'color' => 'neutral',
    'size' => 'sm',
    'icon' => null,
    'outline' => false,
])

@if(!empty($description))
    <x-shared::popover placement="top">
        <x-slot name="trigger">
            <x-shared::badge
                color="{{ $color }}"
                size="{{ $size }}"
                :icon="$icon"
                :outline="$outline"
            >
                {{ $name }}
            </x-shared::badge>
        </x-slot>
        <div>{{ $description }}</div>
    </x-shared::popover>
@else
    <x-shared::badge
        color="{{ $color }}"
        size="{{ $size }}"
        :icon="$icon"
        :outline="$outline"
    >
        {{ $name }}
    </x-shared::badge>
@endif
