<div class="flex flex-col items-center gap-4 surface-read text-on-surface p-4 h-full">
    <h2 class="text-2xl font-bold text-accent">{{ __('dashboard::promotion.title') }}</h2>

    @if ($error)
        <p>{{ $error }}</p>
    @else


        <p class="text-center">{{ __('dashboard::promotion.current_status') }}</p>
        <img src="{{ $theme->asset('icons/seed.png') }}" alt="" class="w-32" />
        @if ($status && $status->status === 'pending')
            <p class="text-center font-medium">{{ __('dashboard::promotion.pending_message') }}</p>
            <div class="flex-1">&nbsp;</div>
        @endif
        @if ($status && $status->status === 'rejected')
            <p>{!! __('dashboard::promotion.rejection_description') !!}</p>
            @if ($status->rejectionReason)
                @if (strlen($status->rejectionReason) > 100)
                    <x-shared::popover :content="$status->rejectionReason">
                         <x-slot name="trigger">
                            <p class="text-accent w-full p-3 border border-error text-sm">
                                {{ substr($status->rejectionReason, 0, 100) }}...
                            </p>
                         </x-slot>
                        <div class="text-accent w-full p-3 border border-error text-sm">
                            <p>{{ $status->rejectionReason }}</p>
                        </div>
                    </x-shared::popover>
                @else
                    <p class="text-accent w-full p-3 border border-error text-sm">
                        {{ $status->rejectionReason }}
                    </p>
                @endif
                
            @endif

            @if($eligibility)
                @php      
                    $nextRequestInDays = ceil($eligibility->daysRequired - $eligibility->daysElapsed);
                @endphp
                @if($nextRequestInDays > 0)
                    {{ trans_choice('dashboard::promotion.rejection_next', $nextRequestInDays, ['days' => $nextRequestInDays]) }}</p>
                @endif
            @endif
            
        @elseif ($eligibility && (!$status || $status->status !== 'pending'))
            <p class="text-center">{{ __('dashboard::promotion.requirements_intro') }}</p>
            <ul class="list-disc list-inside text-left">
                <li>{{ __('dashboard::promotion.requirement_days', ['days' => (int) ceil($eligibility->daysRequired)]) }}</li>
                <li>{{ __('dashboard::promotion.requirement_comments', ['comments' => $eligibility->commentsRequired]) }}
                </li>
            </ul>

            @php
                $daysElapsedDisplay = $eligibility->daysElapsed < $eligibility->daysRequired ? (int) floor($eligibility->daysElapsed): (int) ceil($eligibility->daysRequired);
                $daysRequiredDisplay = (int) ceil($eligibility->daysRequired);
            @endphp
            <div class="w-full space-y-3">
                <x-shared::progress :value="$eligibility->daysRequired > 0
                    ? min(100, ($eligibility->daysElapsed / $eligibility->daysRequired) * 100)
                    : 100" labelPosition="bottom" :label="$daysElapsedDisplay .
                    '/' .
                    $daysRequiredDisplay .
                    ' ' .
                    __('dashboard::promotion.days_label')" />
                <x-shared::progress :value="$eligibility->commentsRequired > 0
                    ? min(100, ($eligibility->commentsPosted / $eligibility->commentsRequired) * 100)
                    : 100" labelPosition="bottom" :label="$eligibility->commentsPosted .
                    '/' .
                    $eligibility->commentsRequired .
                    ' ' .
                    __('dashboard::promotion.comments_label')" />
            </div>
        @endif

        <form class="flex-1 flex flex-col justify-end" action="{{ route('dashboard.promotion.request') }}" method="POST">
            @csrf
            <x-shared::button type="submit" color="accent" :disabled="!$eligibility->eligible">
                @if ($status && $status->status === 'pending')
                    {{ __('dashboard::promotion.button_pending') }}
                @else
                    {{ __('dashboard::promotion.button_request') }}
                @endif
            </x-shared::button>
        </form>
    @endif
</div>
