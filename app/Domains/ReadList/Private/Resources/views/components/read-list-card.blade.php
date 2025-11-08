@props([
    // App\Domains\ReadList\ViewModels\ReadListStoryViewModel
    'item',
])

<div class="surface-read text-on-surface p-4 grid grid-cols-[auto_1fr] lg:grid-cols-[auto_30%_auto_1fr] lg:grid-rows-[auto_1fr_auto] gap-x-4 gap-y-2"
    x-data="readListCard({{ $item->id }})">

    {{-- Cover --}}
    <div class="col-start-1 row-start-1 row-span-3 w-[115px] h-[153px] md:w-[230px] md:h-[306px] mx-auto overflow-hidden">
        <a href="{{ url('/stories/' . $item->slug) }}" class="block">
            <img src="{{ asset('images/story/default-cover.svg') }}" alt="{{ $item->title }}"
                class="w-[115px] lg:w-[230px] object-contain">
        </a>
    </div>

    <div class="col-start-2 col-span-1 row-span-3 flex flex-col gap-4">
        {{-- Title + summary tooltip icon --}}
        <div class="flex flex-col">
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

        <div class="flex-1 flex flex-col justify-between">
            @if (!empty($item->genreNames))
                <div class="flex flex-wrap gap-2">
                    @foreach ($item->genreNames as $g)
                        <x-shared::badge color="accent">{{ $g }}</x-shared::badge>
                    @endforeach
                </div>
            @endif

            {{-- Authors --}}
            <div class="mt-1 text-sm text-gray-600 overflow-hidden text-ellipsis font-medium">
                {{ __('story::shared.by') }}
                <x-profile::inline-names :profiles="$item->authors" />
            </div>
        </div>

        <div class="flex items-center justify-between text-sm font-bold">
            <div class="flex items-center gap-2 text-gray-600">

                <span>{!! trans_choice('story::shared.metrics.chapters', $item->totalChaptersCount, [
                    'count' => '<span class="text-accent">' . $item->totalChaptersCount . '</span>',
                ]) !!}</span>
                @if ($item->hasChapters())
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

     {{-- Bottom right = keep reading --}}
    <div class="col-start-2 row-start-4 lg:col-start-4 lg:row-start-3 ml-auto">
        @if ($item->keepReadingUrl)
            <a href="{{ $item->keepReadingUrl }}" class="btn btn-primary">
                <x-shared::button color="accent">
                    {{ __('readlist::page.keep_reading') }}
                </x-shared::button>
            </a>
        @endif
    </div>

     {{--  Bottom center = progress bar --}}
    @if ($item->hasChapters())
    <div class="row-start-4 lg:row-start-3 lg:col-start-3 lg:col-span-1 ml-auto my-auto max-w-[10rem]">
        <x-shared::progress :value="$item->progressPercent" />
    </div>
    @endif

    {{-- Top right = last update + toogle --}}
    <div class="row-start-5 col-span-2 lg:row-start-1 lg:col-start-3 lg:col-span-2 ml-auto flex items-center gap-8">
        <div class="text-fg/70" x-data="{ lastModified: new Date('{{ $item->lastModified?->toISOString() }}') }">
            {{ __('readlist::page.last_updated_at') }} <span x-text="DateUtils.formatDate(lastModified)"></span>
        </div>
        @if ($item->hasChapters())
            <div x-on:click="loadChapters()" :aria-expanded="isOpen"
                aria-label="{{ __('readlist::page.toggle_chapters') }}">
                <x-shared::badge color="accent" :outline="true">
                <span x-show="!isOpen && !isLoading" class="material-symbols-outlined">keyboard_arrow_up</span>
                <span x-show="isOpen && !isLoading" class="material-symbols-outlined">keyboard_arrow_down</span>
                <span x-show="isLoading" class="material-symbols-outlined animate-spin">refresh</span>
            </x-shared::badge>
        </div>
        @endif
    </div>

    {{-- Chapters list --}}
    <div class="bg-bg p-2 lg:p-4 col-start-1 row-start-6 col-span-2 lg:col-start-1 lg:col-span-4 lg:row-start-4 lg:row-span-1"
        x-show="isOpen" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2">
        
        <div x-show="!isLoading" x-html="chaptersHtml"></div>
        <div x-show="isLoading" class="flex items-center justify-center py-8">
            <span class="material-symbols-outlined animate-spin text-2xl">refresh</span>
        </div>
    </div>
</div>
