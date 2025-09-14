@props([
// App\Domains\Story\ViewModels\StorySummaryViewModel
'item',
'displayAuthors' => true,
])

@php(
$genres = $item->getGenreNames()
)
@php(
$tws = $item->getTriggerWarningNames()
)

<div class="group block transition overflow-hidden w-[230px]">

    <div class="w-[230px] h-[295px] mx-auto overflow-hidden">
        <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
            <img
                src="{{ asset('images/story/default-cover.svg') }}"
                alt="{{ $item->getTitle() }}"
                class="w-[230px] h-[295px] object-contain group-hover:scale-105 transition">
        </a>
    </div>

    <div class="p-4 pt-3">
        {{-- Genres row: show max 3, overflow as +X with tooltip --}}
        @if(!empty($genres))
        @php($visible = array_slice($genres, 0, 3))
        @php($extra = array_slice($genres, 3))
        <div class="mb-2 flex flex-nowrap items-center gap-2 overflow-hidden">
            @foreach($visible as $g)
            <span class="inline-block flex-shrink-0 text-[12px] leading-none px-2 py-1 rounded bg-accent text-white whitespace-nowrap overflow-hidden text-ellipsis max-w-[210px]">{{ $g }}</span>
            @endforeach
            @if(count($extra) > 0)
            <x-shared::popover placement="top" width="16rem">
                <x-slot name="trigger">
                    <button type="button" aria-label="{{ __('story::shared.genres.label') }}"
                        class="inline-flex flex-shrink-0 items-center text-[12px] leading-none px-2 py-1 rounded bg-accent/10 text-accent ring-1 ring-inset ring-accent/30 hover:bg-accent/20 whitespace-nowrap">
                        +{{ count($extra) }}
                    </button>
                </x-slot>
                <div class="font-semibold text-gray-900 mb-1">{{ __('story::shared.genres.label') }}</div>
                <ul class="list-disc pl-5 text-sm text-gray-800">
                    @foreach($extra as $eg)
                    <li>{{ $eg }}</li>
                    @endforeach
                </ul>
            </x-shared::popover>
            @endif
        </div>
        @endif

        {{-- Title + summary tooltip icon --}}
        <div class="flex items-start gap-2">
            <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
                <h2 class="flex-1 font-semibold text-gray-900 text-[16px] leading-5 line-clamp-2">{{ $item->getTitle() }}</h2>
            </a>
            @if(trim($item->getDescription()) !== '')
            <x-shared::tooltip type="info" :title="__('story::shared.description.label')" placement="right" width="20rem">
                {{ strip_tags($item->getDescription()) }}
            </x-shared::tooltip>
            @endif
        </div>

        {{-- Authors --}}
        @if($displayAuthors)
        <div class="mt-1 text-[12px] text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis font-medium">
            {{ __('story::shared.by') }}
            <x-profile::inline-names :profiles="$item->getAuthors()" />
        </div>
        @endif
    </div>
    </a>

    {{-- Bottom meta row: chapters and words (hardcoded for now) + TW icon/tooltip --}}
    <div class="px-4 pb-4 border-t border-gray-200">
        <div class="flex items-center justify-between text-[12px] text-gray-700">
            <div class="flex items-center gap-2">
                <span>{{ trans_choice('story::shared.metrics.chapters', 8, ['count' => 8]) }}</span>
                <span class="text-gray-400">|</span>
                <span>{{ trans_choice('story::shared.metrics.words', 15000, ['count' => '15k']) }}</span>
            </div>
            @if(!empty($tws))
            <x-shared::popover placement="left" width="18rem">
                <x-slot name="trigger">
                    <button type="button" aria-label="{{ __('story::shared.trigger_warnings.label') }}"
                        class="inline-flex items-center justify-center h-5 w-5 rounded-full text-red-600 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                        <span class="material-symbols-outlined text-[18px] leading-none">warning</span>
                    </button>
                </x-slot>
                <div class="font-semibold text-gray-900 mb-1">{{ __('story::shared.trigger_warnings.label') }}</div>
                <ul class="list-disc pl-5 text-sm text-gray-800">
                    @foreach($tws as $tw)
                    <li>{{ $tw }}</li>
                    @endforeach
                </ul>
            </x-shared::popover>
            @endif
        </div>
    </div>
</div>