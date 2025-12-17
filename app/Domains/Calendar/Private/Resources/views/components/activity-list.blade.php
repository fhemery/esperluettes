@php
/** @var array<\App\Domains\Calendar\Public\Contracts\ActivityDto> $activities */
@endphp

<div class="h-full flex flex-col gap-8 min-w-0 surface-read text-on-surface p-4">
    <div class="flex items-center justify-between text-accent">
        <x-shared::title tag="h2" icon="event">
            {{ __('calendar::activity.list.title') }}
        </x-shared::title>
    </div>

    @if(empty($activities))
        <div class="flex-1 flex flex-col justify-center text-center py-8 text-gray-600">
            <span class="material-symbols-outlined text-6xl text-on-surface/30">event_busy</span>
            <p class="mt-4">{{ __('calendar::activity.list.no_activities') }}</p>
        </div>
    @else
        <x-story::scroller>
            @foreach($activities as $activity)
                <x-calendar::activity-card :activity="$activity" />
            @endforeach
            <div class="w-[230px] h-full flex flex-col justify-center items-center gap-4">
                <div>
                    <img src="{{ $theme->asset('not-ready.png') }}" alt="{{ __('dashboard::index.placeholder_text') }}" class="max-w-full h-auto">
                </div>
                <div class="text-sm text-center">{{__('calendar::activity.list.no_more_activities')}}</div>
            </div>
        </x-story::scroller>
    @endif
</div>
