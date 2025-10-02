@php
/** @var array<\App\Domains\Story\Private\ViewModels\StorySummaryViewModel> $vms */
    @endphp

    <div class="flex flex-col gap-4 min-w-0 surface-read text-on-surface p-4 pb-6">
        <div class="flex items-center justify-between text-accent">

            <h3 class="text-xl font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined">
                    nest_eco_leaf
                </span>
                {{ __('story::discover.title') }}
            </h3>
            <a href="{{ route('stories.index') }}" class="text-sm hover:underline">{{ __('story::discover.view_all') }}</a>
        </div>

        <x-story::scroller>
            @foreach($vms as $vm)
            <x-story::card :item="$vm" />
            @endforeach

            {{-- Placeholder CTA as last slide --}}
            <div class="shrink-0 w-[230px] h-[356px] flex flex-col items-center justify-center gap-32">
                <div class="w-full text-center">
                    {{__('story::discover.placeholder_label') }}
                </div>
                <a href="{{ route('stories.index') }}">
                    <x-shared::button color="accent" size="lg">
                        {{ __('story::discover.placeholder_cta') }}
                    </x-shared::button>
                </a>
            </div>
        </x-story::scroller>
    </div>