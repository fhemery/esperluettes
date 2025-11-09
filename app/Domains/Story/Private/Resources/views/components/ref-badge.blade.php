@props([
    'name' => '',
    'description' => null,
    'color' => 'neutral',
    'size' => 'sm',
    'icon' => null,
    'outline' => false,
    'wrap' => false
])

@php
    $decodedName = is_string($name)
        ? html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : $name;
    $decodedDescription = is_string($description)
        ? html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : $description;
@endphp

@if(!empty($decodedDescription))
    <x-shared::popover placement="top">
        <x-slot name="trigger">
            <x-shared::badge
                color="{{ $color }}"
                size="{{ $size }}"
                :icon="$icon"
                :outline="$outline"
                :wrap="$wrap"
            >
                {{ $decodedName }}
            </x-shared::badge>
        </x-slot>
        <div>{{ $decodedDescription }}</div>
    </x-shared::popover>
@else
    <x-shared::badge
        color="{{ $color }}"
        size="{{ $size }}"
        :icon="$icon"
        :outline="$outline"
        :wrap="$wrap"
    >
        {{ $decodedName }}
    </x-shared::badge>
@endif
