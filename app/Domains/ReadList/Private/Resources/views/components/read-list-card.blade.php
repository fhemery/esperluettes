@props([
    // App\Domains\ReadList\ViewModels\ReadListStoryViewModel
    'item',
])

<div class="grid grid-cols-[auto_30%_1fr] grid-rows-[auto_1fr_auto_auto] gap-4">

    {{-- Cover --}}
    <div class="row-span-3 col-span-1 w-[230px] h-[306px] mx-auto overflow-hidden">
        <a href="{{ url('/stories/' . $item->slug) }}" class="block">
            <img src="{{ asset('images/story/default-cover.svg') }}" alt="{{ $item->title }}"
                class="w-[230px] object-contain">
        </a>
    </div>

    <div class="col-span-1">
        {{-- Title + summary tooltip icon --}}
        <div class="flex items-center gap-1">
            <a href="{{ url('/stories/' . $item->slug) }}" class="block">
                <x-shared::title tag="h2" class="hover:underline">{{ $item->title }}</x-shared::title>
            </a>
            @if (trim($item->description) !== '')
                <div class="mb-1">
                    <x-shared::tooltip type="info" :title="__('story::shared.description.label')" placement="right" maxWidth="20rem"
                        iconClass="text-black">
                        {{ strip_tags($item->description) }}
                    </x-shared::tooltip>
                </div>
            @endif
        </div>
    </div>

    <div class="col-start-2 row-start-2 row-span-1 col-span-1 flex flex-col justify-between gap-4">
        @if (!empty($item->genreNames))
            <div class="flex flex-wrap gap-2">
                @foreach ($item->genreNames as $g)
                    <x-shared::badge color="accent">{{ $g }}</x-shared::badge>
                @endforeach
            </div>
        @endif



        {{-- Authors --}}
        <div class="mt-1 text-sm text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis font-medium">
            {{ __('story::shared.by') }}
            <x-profile::inline-names :profiles="$item->authors" />
        </div>
    </div>

    {{-- Bottom meta row: chapters and words + TW icon/tooltip --}}
    <div class="col-start-2 row-start-3 row-span-1 col-span-1 border-t border-gray-700 pt-2">
        <div class="flex items-center justify-between text-sm font-bold">
            <div class="flex items-center gap-2 text-gray-600">

                <span>{!! trans_choice('story::shared.metrics.chapters', $item->totalChaptersCount, [
                    'count' => '<span class="text-accent">' . $item->totalChaptersCount . '</span>',
                ]) !!}</span>
                @if ($item->totalChaptersCount > 0)
                    <span class="text-gray-400">|</span>
                    <span>{!! trans_choice('story::shared.metrics.words', $item->totalWordCount, [
                        'count' =>
                            '<span class="text-accent">' .
                            \App\Domains\Shared\Support\NumberFormatter::compact($item->totalWordCount) .
                            '</span>',
                    ]) !!}</span>
                @endif
            </div>
            <x-story::trigger-warnings :items="$item->triggerWarningNames" :disclosure="$item->twDisclosure" />
        </div>
    </div>
    {{--  Bottom = progress bar + keep reading button --}}
    <div class="col-start-1 row-start-4">
        <x-shared::progress :value="$item->progressPercent" :label="__('readlist::page.progress.label', ['progress' => $item->progressPercent])" />
    </div>
    <div class="col-start-2 row-start-4 ml-auto mt-auto">
        @if ($item->keepReadingUrl)
            <a href="{{ $item->keepReadingUrl }}" class="btn btn-primary">
                <x-shared::button color="accent">
                    {{ __('readlist::page.keep_reading') }}
                </x-shared::button>
            </a>
        @endif
    </div>

    {{-- Chapters list --}}
    <div class="col-start-3 row-start-1 row-span-4">
        <x-read-list::read-list-chapters :chaptersViewModel="$item->chapters" :story="$item" />
    </div>
</div>
