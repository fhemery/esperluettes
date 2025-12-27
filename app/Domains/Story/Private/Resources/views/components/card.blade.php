@props([
// App\Domains\Story\ViewModels\StorySummaryViewModel
'item',
'displayAuthors' => true,
'light' => false,
])

@php
    $genres = $light ? [] : $item->getGenreNames();
    $tws = $light ? [] : $item->getTriggerWarningNames();
    $twDisclosure = $light ? null : $item->getTwDisclosure();
@endphp

<div class="flex-1 group flex flex-col overflow-hidden w-[230px]">

    <div class="w-[230px] h-[306px] mx-auto overflow-hidden">
        <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
            <x-shared::default-cover class="w-[230px] object-contain" />
        </a>
    </div>

    <div class="pb-1 pt-2 flex-1 flex flex-col gap-1">
        @if(!$light && !empty($genres))
            <x-shared::badge-overflow>
                @foreach($genres as $g)
                    <x-shared::badge color="accent" size="xs">{{ $g }}</x-shared::badge>
                @endforeach
            </x-shared::badge-overflow>
        @endif

        {{-- Title + summary tooltip icon --}}
        <div class="flex items-center gap-1">
            <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
                <div class="flex-1 font-extrabold text-gray-900 text-md leading-5 line-clamp-2 hover:underline">{{ $item->getTitle() }}</div>
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

    {{-- Bottom meta row: chapters and words + TW icon/tooltip (hidden in light mode) --}}
    @if(!$light)
    <div class="pb-1 border-b border-gray-700">
        <div class="flex items-center justify-between text-sm font-bold">
            <div class="flex items-center gap-2 text-gray-600">

                <span>{!! trans_choice('story::shared.metrics.chapters', $item->getChaptersCount(), ['count' => '<span class="text-accent">'. $item->getChaptersCount() . '</span>']) !!}</span>
                @if($item->getChaptersCount() > 0)
                    <span class="text-gray-400">|</span>
                    <span>{!! trans_choice('story::shared.metrics.words', $item->getWordsTotal(), ['count' => '<span class="text-accent">'. \App\Domains\Shared\Support\NumberFormatter::compact($item->getWordsTotal()) . '</span>']) !!}</span>
                @endif
            </div>
            <x-story::trigger-warnings :items="$tws" :disclosure="$twDisclosure" />
        </div>
    </div>
    @endif
</div>