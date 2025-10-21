@php
/** @var \App\Domains\Calendar\Private\Models\Activity $activity */
use App\Domains\Calendar\Public\Contracts\ActivityState;
$stateLabel = match($activity->state) {
    ActivityState::ACTIVE => __('calendar::activity.state.active'),
    ActivityState::PREVIEW => __('calendar::activity.state.preview'),
    ActivityState::ENDED => __('calendar::activity.state.ended'),
    default => __('calendar::activity.state.draft'),
};
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <x-shared::title tag="h1" icon="event">
                    {{ $activity->name }}
                </x-shared::title>
                <x-shared::badge color="accent" :outline="true">{{ $stateLabel }}</x-shared::badge>
            </div>

            <div class="text-sm text-fg/80">
                @if($activity->active_starts_at)
                    {{ __('calendar::activity.starts') }}: {{ $activity->active_starts_at->isoFormat('L') }}
                @endif
                @if($activity->active_ends_at)
                    • {{ __('calendar::activity.ends') }}: {{ $activity->active_ends_at->isoFormat('L') }}
                @endif
            </div>

            @if($activity->image_path)
                <img src="{{ asset('storage/' . $activity->image_path) }}" alt="{{ $activity->name }}" class="max-w-full h-auto max-h-[300px] object-cover object-center rounded">
            @endif

            <div class="prose max-w-none">
                {!! $activity->description !!}
            </div>

            <x-dynamic-component :component="$componentKey" :activity="$activity" />
        </div>
    </div>
</x-app-layout>
