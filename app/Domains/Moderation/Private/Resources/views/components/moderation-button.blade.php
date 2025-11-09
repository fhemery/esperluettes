@props([
    'badgeColor' => 'warning',
    'position' => 'top',
    'id' => null,
])

<x-shared::popover :position="$position" :displayOnHover="false">
    <x-slot name="trigger">
        <x-shared::badge :color="$badgeColor" :outline="false">
            <span class="material-symbols-outlined text-[20px] leading-none">
                report
            </span>
        </x-shared::badge>
    </x-slot>
    <div class="flex flex-col gap-2" @if($id) id="{{ $id }}" @endif>
        <x-shared::title tag="h3">{{ __('moderation::moderation.popup_title') }}</x-shared::title>
        {{ $slot }}
    </div>
</x-shared::popover>
