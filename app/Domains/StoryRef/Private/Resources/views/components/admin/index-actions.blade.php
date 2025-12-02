@props([
    'exportRoute',
    'exportLabel',
    'createRoute',
    'createLabel',
    'itemCount' => 0,
])

<div class="flex items-center gap-2">
    <a href="{{ $exportRoute }}" x-show="!reordering">
        <x-shared::button color="neutral" :outline="true">
            <span class="material-symbols-outlined text-[18px] leading-none">download</span>
            {{ $exportLabel }}
        </x-shared::button>
    </a>
    @if($itemCount > 1)
        <x-shared::button 
            color="neutral" 
            :outline="true" 
            x-show="!reordering"
            x-on:click="reordering = true"
        >
            <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
            {{ __('administration::reorder.button') }}
        </x-shared::button>
    @endif
    <a href="{{ $createRoute }}">
        <x-shared::button color="primary" icon="add">
            {{ $createLabel }}
        </x-shared::button>
    </a>
</div>
