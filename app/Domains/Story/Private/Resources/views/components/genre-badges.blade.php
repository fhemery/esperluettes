@props([
    'genres' => [],
    'placement' => 'right',
    'maxWidth' => '20rem',
    'color' => 'accent', // badge color variant
])

<div {{ $attributes->merge(['class' => 'mb-1 flex flex-nowrap items-center gap-2 overflow-hidden']) }}>
    @foreach($shown as $g)
        <x-shared::badge :color="$color" size="xs">{{ $g }}</x-shared::badge>
    @endforeach

    @if(count($hidden) > 0)
        <x-shared::popover :placement="$placement" :maxWidth="$maxWidth">
            <x-slot name="trigger">
                <x-shared::badge :color="$color" size="xs">+{{ count($hidden) }}</x-shared::badge>
            </x-slot>
            <div class="flex flex-wrap gap-2">
                @foreach($hidden as $eg)
                    <x-shared::badge :color="$color" size="xs">{{ $eg }}</x-shared::badge>
                @endforeach
            </div>
        </x-shared::popover>
    @endif
</div>
