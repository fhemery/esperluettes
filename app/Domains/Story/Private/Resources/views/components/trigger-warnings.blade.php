@props([
    'items' => [],
    'disclosure' => null,
])

@if(!empty($items))
    <x-shared::popover placement="top">
        <x-slot name="trigger">
            <button type="button" aria-label="{{ __('story::shared.trigger_warnings.label') }}"
                class="inline-flex items-center justify-center h-5 w-5 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500/40"
                title="{{ __('story::shared.trigger_warnings.tooltips.listed') }}">
                <span class="translate-y-0.5 material-symbols-outlined text-[18px] leading-none text-error">warning</span>
            </button>
        </x-slot>
        <div class="font-semibold text-gray-900 mb-1">{{ __('story::shared.trigger_warnings.label') }}</div>
        <div class="flex flex-wrap gap-2 ">
            @foreach($items as $tw)
            <x-shared::badge color="error" size="xs">{{ $tw }}</x-shared::badge>
            @endforeach
        </div>
    </x-shared::popover>
@elseif($disclosure === 'no_tw')
    <x-shared::popover placement="top">
        <x-slot name="trigger">
            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full">
                <span class="translate-y-0.5 material-symbols-outlined text-[18px] leading-none text-success">warning_off</span>
            </span>
        </x-slot>
        <div>{{ __('story::shared.trigger_warnings.tooltips.no_tw') }}</div>
    </x-shared::popover>
@elseif($disclosure === 'unspoiled')
    <x-shared::popover placement="top">
        <x-slot name="trigger">
            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full">
                <span class="translate-y-0.5 material-symbols-outlined text-[18px] leading-none text-warning">help</span>
            </span>
        </x-slot>
        <div>{{ __('story::shared.trigger_warnings.tooltips.unspoiled') }}</div>
    </x-shared::popover>
@endif
