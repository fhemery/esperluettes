@props([
// App\Domains\Story\ViewModels\StorySummaryViewModel
'item',
'displayAuthors' => true,
])

@php
    $genres = $item->getGenreNames();
    $tws = $item->getTriggerWarningNames();
    $twDisclosure = $item->getTwDisclosure();
@endphp

<div class="group flex flex-col overflow-hidden w-[230px]">

    <div class="w-[230px] h-[306px] mx-auto overflow-hidden">
        <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
            <img
                src="{{ asset('images/story/default-cover.svg') }}"
                alt="{{ $item->getTitle() }}"
                class="w-[230px] object-contain">
        </a>
    </div>

    <div class="pb-1 pt-2 flex-1 flex flex-col">
        @if(!empty($genres))
            <x-story::genre-badges :genres="$genres" placement="right" color="accent" />
        @endif

        {{-- Title + summary tooltip icon --}}
        <div class="flex items-center gap-1">
            <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
                <h2 class="flex-1 font-extrabold text-gray-900 text-md leading-5 line-clamp-2 hover:underline">{{ $item->getTitle() }}</h2>
            </a>
            @if(trim($item->getDescription()) !== '')
            <div class="-mb-1">
                <x-shared::tooltip type="info" :title="__('story::shared.description.label')" placement="right" maxWidth="20rem" iconClass="text-black">
                    {{ strip_tags($item->getDescription()) }}
                </x-shared::tooltip>
            </div>
            @endif
        </div>

        {{-- Authors --}}
        @if($displayAuthors)
        <div class="mt-1 text-sm text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis font-medium">
            {{ __('story::shared.by') }}
            <x-profile::inline-names :profiles="$item->getAuthors()" />
        </div>
        @endif
    </div>
    </a>

    {{-- Bottom meta row: chapters and words + TW icon/tooltip --}}
    <div class="pb-1 border-b border-gray-700">
        <div class="flex items-center justify-between text-sm font-bold">
            <div class="flex items-center gap-2 text-gray-600">

                <span>{!! trans_choice('story::shared.metrics.chapters', $item->getChaptersCount(), ['count' => '<span class="text-accent">'. $item->getChaptersCount() . '</span>']) !!}</span>
                @if($item->getChaptersCount() > 0)
                    <span class="text-gray-400">|</span>
                    <span>{!! trans_choice('story::shared.metrics.words', $item->getWordsTotal(), ['count' => '<span class="text-accent">'. \App\Domains\Shared\Support\NumberFormatter::compact($item->getWordsTotal()) . '</span>']) !!}</span>
                @endif
            </div>
            @if(!empty($tws))
                <x-shared::popover placement="top">
                    <x-slot name="trigger">
                        <button type="button" aria-label="{{ __('story::shared.trigger_warnings.label') }}"
                            class="pt-2 inline-flex items-center justify-center h-5 w-5 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500/40"
                            title="{{ __('story::shared.trigger_warnings.tooltips.listed') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none text-error">warning</span>
                        </button>
                    </x-slot>
                    <div class="font-semibold text-gray-900 mb-1">{{ __('story::shared.trigger_warnings.label') }}</div>
                    <div class="flex flex-wrap gap-2 ">
                        @foreach($tws as $tw)
                        <x-shared::badge color="error" size="xs">{{ $tw }}</x-shared::badge>
                        @endforeach
                    </div>
                </x-shared::popover>
            @elseif($twDisclosure === 'no_tw')
                <x-shared::popover placement="top">
                    <x-slot name="trigger">
                        <span class="pt-2 inline-flex items-center justify-center h-5 w-5 rounded-full">
                            <span class="material-symbols-outlined text-[18px] leading-none text-success">check_circle</span>
                        </span>
                    </x-slot>
                    <div>{{ __('story::shared.trigger_warnings.tooltips.no_tw') }}</div>
                </x-shared::popover>
            @elseif($twDisclosure === 'unspoiled')
            <x-shared::popover placement="top">
                    <x-slot name="trigger">
                        <span class="pt-2 inline-flex items-center justify-center h-5 w-5 rounded-full">
                            <span class="material-symbols-outlined text-[18px] leading-none text-warning">help</span>
                        </span>
                    </x-slot>
                    <div>{{ __('story::shared.trigger_warnings.tooltips.unspoiled') }}</div>
                </x-shared::popover>
            @endif
        </div>
    </div>
</div>