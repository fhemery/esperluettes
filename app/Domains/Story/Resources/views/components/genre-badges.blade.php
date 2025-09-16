@props([
    'genres' => [],
    'placement' => 'right',
    'maxWidth' => '20rem',
    'color' => 'accent', // badge color variant
])

<div {{ $attributes->merge(['class' => 'mb-2 flex flex-nowrap items-center gap-2 overflow-hidden']) }}>
    @foreach($shown as $g)
        <x-shared::badge :color="$color" size="sm">{{ $g }}</x-shared::badge>
    @endforeach

    @if(count($hidden) > 0)
        <x-shared::popover :placement="$placement" :maxWidth="$maxWidth">
            <x-slot name="trigger">
                <x-shared::badge :color="$color" size="sm" class="min-w-[40px] justify-center">+{{ count($hidden) }}</x-shared::badge>
            </x-slot>
            <div class="flex flex-wrap gap-2">
                @foreach($hidden as $eg)
                    <x-shared::badge :color="$color" size="sm">{{ $eg }}</x-shared::badge>
                @endforeach
            </div>
        </x-shared::popover>
    @endif
</div>
