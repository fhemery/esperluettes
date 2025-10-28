@props([
/** @var \App\Domains\Calendar\Public\Contracts\ActivityDto */
'activity',
])

@php
use App\Domains\Calendar\Public\Contracts\ActivityState;

$stateBadgeConfig = match($activity->state) {
ActivityState::ACTIVE => ['color' => 'success', 'label' => __('calendar::activity.state.active')],
ActivityState::PREVIEW => ['color' => 'neutral', 'label' => __('calendar::activity.state.preview')],
ActivityState::ENDED => ['color' => 'neutral', 'label' => __('calendar::activity.state.ended')],
default => ['color' => 'neutral', 'label' => __('calendar::activity.state.draft')],
};
@endphp

<div class="h-full flex-1 group flex flex-col gap-2 overflow-hidden w-[230px]">

    {{-- Image --}}
    <div class="w-[230px] h-[220px] mx-auto overflow-hidden">
        <a href="{{ route('calendar.activities.show', $activity->slug) }}" class="block">
            @if($activity->image_path)
            <img
                src="{{ asset('storage/' . $activity->image_path) }}"
                alt="{{ $activity->name }}"
                class="w-[230px] h-[220px] object-cover">
            @else
            <div class="w-[230px] h-[220px] surface-neutral flex items-center justify-center">
                <span class="material-symbols-outlined text-6xl text-on-surface/30">event</span>
            </div>
            @endif
        </a>
    </div>

    <div class="flex-1 flex flex-col gap-2">


        {{-- Title --}}
        <div class="flex gap-2 flex-wrap items-center">
            <a href="{{ route('calendar.activities.show', $activity->slug) }}" class="block">
                <x-shared::title tag="h3" class="hover:underline">
                    {{ $activity->name }}
                </x-shared::title>
                {{-- State badge --}}
            </a>

            <div>
                <x-shared::badge :color="$stateBadgeConfig['color']" :outline="false" size="xs">
                    {{ $stateBadgeConfig['label'] }}
                </x-shared::badge>
            </div>
        </div>
        {{-- Dates --}}
        <div class="text-xs text-fg/80">
            @if($activity->active_starts_at)
            {{ $activity->active_starts_at->isoFormat('L') }}
            @endif
            -
            @if($activity->active_ends_at)
            {{ $activity->active_ends_at->isoFormat('L') }}
            @endif
        </div>

        {{-- Description --}}
        <div class="flex-1 text-sm text-fg line-clamp-6">
            {!! $activity->description !!}
        </div>
    </div>

    {{-- View More Button --}}
    <div>
        <a href="{{ route('calendar.activities.show', $activity->slug) }}">
            <x-shared::button color="accent" size="md" class="w-full">
                {{ __('calendar::activity.view_more') }}
            </x-shared::button>
        </a>
    </div>
</div>