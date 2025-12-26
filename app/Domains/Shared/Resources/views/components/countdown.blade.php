@if($isFinished)
    <span class="{{ $class }}">{{ __('shared::countdown.finished') }}</span>
@else
    <span 
        id="{{ $componentId }}"
        x-data="countdownTimer"
        x-init="init()"
        data-end-time="{{ $endTime }}"
        data-update-interval="{{ $updateInterval }}"
        data-show-seconds="{{ $showSeconds ? 'true' : 'false' }}"
        data-trans-day="{{ __('shared::countdown.day') }}"
        data-trans-days="{{ __('shared::countdown.days') }}"
        data-trans-hour="{{ __('shared::countdown.hour') }}"
        data-trans-hours="{{ __('shared::countdown.hours') }}"
        data-trans-minute="{{ __('shared::countdown.minute') }}"
        data-trans-minutes="{{ __('shared::countdown.minutes') }}"
        data-trans-second="{{ __('shared::countdown.second') }}"
        data-trans-seconds="{{ __('shared::countdown.seconds') }}"
        data-trans-separator="{{ __('shared::countdown.separator') }}"
        data-trans-finished="{{ __('shared::countdown.finished') }}"
        class="{{ $class }}"
    >
        {{ __('shared::countdown.loading') }}
    </span>
@endif
